<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchaseInvoiceService
{
    public function __construct(
        private readonly InventoryNumberGenerator $numberGenerator,
        private readonly AccountingService $accounting,
    ) {}

    /**
     * @return LengthAwarePaginator<int, PurchaseInvoice>
     */
    public function paginate(): LengthAwarePaginator
    {
        return PurchaseInvoice::query()
            ->with(['supplier', 'purchaseOrder', 'goodsReceipt'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id): PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->with(['supplier', 'purchaseOrder', 'goodsReceipt', 'lines.item', 'payments'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFromGoodsReceipt(int $goodsReceiptId, array $attributes): PurchaseInvoice
    {
        return DB::transaction(function () use ($goodsReceiptId, $attributes): PurchaseInvoice {
            $goodsReceipt = GoodsReceipt::query()
                ->with(['purchaseOrder', 'supplier', 'lines.item', 'lines.purchaseOrderLine'])
                ->lockForUpdate()
                ->findOrFail($goodsReceiptId);

            if ($goodsReceipt->status !== 'delivered' || $goodsReceipt->invoices()->exists()) {
                throw ValidationException::withMessages(['goods_receipt' => 'Goods receipt belum selesai atau sudah memiliki invoice.']);
            }

            $invoice = PurchaseInvoice::query()->create([
                'invoice_number' => $this->numberGenerator->next('PINV', $attributes['invoice_date']),
                'goods_receipt_id' => $goodsReceipt->id,
                'purchase_order_id' => $goodsReceipt->purchase_order_id,
                'supplier_id' => $goodsReceipt->purchaseOrder->supplier_id,
                'invoice_date' => $attributes['invoice_date'],
                'due_date' => $attributes['due_date'] ?? null,
                'status' => 'open',
                'total_amount' => '0.00',
                'paid_amount' => '0.00',
                'notes' => $attributes['notes'] ?? null,
            ]);

            $totalCents = 0;
            foreach ($goodsReceipt->lines as $goodsReceiptLine) {
                $purchaseOrderLine = $goodsReceiptLine->purchaseOrderLine;
                $amountCents = (int) round((float) $goodsReceiptLine->qty * (float) $purchaseOrderLine->unit_price * 100);
                $totalCents += $amountCents;

                PurchaseInvoiceLine::query()->create([
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $goodsReceiptLine->item_id,
                    'description' => $purchaseOrderLine->item->name,
                    'qty' => $goodsReceiptLine->qty,
                    'unit_price' => $purchaseOrderLine->unit_price,
                    'amount' => number_format($amountCents / 100, 2, '.', ''),
                ]);
            }

            $invoice->update(['total_amount' => number_format($totalCents / 100, 2, '.', '')]);

            $this->accounting->post($invoice, $invoice->invoice_date->toDateString(), $invoice->invoice_number, "Invoice pembelian {$invoice->invoice_number}", [
                ['account_code' => '2190', 'debit_cents' => $totalCents, 'credit_cents' => 0, 'description' => 'Realisasi penerimaan barang'],
                ['account_code' => '2110', 'debit_cents' => 0, 'credit_cents' => $totalCents, 'description' => 'Utang usaha supplier'],
            ]);

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordPayment(int $invoiceId, array $attributes): Payment
    {
        return DB::transaction(function () use ($invoiceId, $attributes): Payment {
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoiceId);
            $remainingCents = $this->toCents($invoice->total_amount) - $this->toCents($invoice->paid_amount);
            $paymentCents = $this->toCents((string) $attributes['amount']);
            if ($invoice->status === 'paid' || $paymentCents > $remainingCents) {
                throw ValidationException::withMessages(['amount' => 'Pembayaran melebihi sisa tagihan atau invoice sudah lunas.']);
            }

            $payment = $invoice->payments()->create([
                'payment_date' => $attributes['payment_date'],
                'amount' => $attributes['amount'],
                'payment_method' => $attributes['payment_method'],
                'reference' => $attributes['reference'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $paidCents = $this->toCents($invoice->paid_amount) + $paymentCents;
            $invoice->update([
                'paid_amount' => number_format($paidCents / 100, 2, '.', ''),
                'status' => $paidCents >= $this->toCents($invoice->total_amount) ? 'paid' : 'partial',
            ]);

            $amountCents = $this->toCents((string) $payment->amount);
            $this->accounting->post($payment, $payment->payment_date->toDateString(), $payment->reference ?: $invoice->invoice_number, "Pembayaran invoice pembelian {$invoice->invoice_number}", [
                ['account_code' => '2110', 'debit_cents' => $amountCents, 'credit_cents' => 0, 'description' => 'Pelunasan utang usaha'],
                ['account_code' => $this->accounting->cashAccountCode($payment->payment_method), 'debit_cents' => 0, 'credit_cents' => $amountCents, 'description' => 'Pembayaran supplier'],
            ]);

            return $payment;
        });
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
