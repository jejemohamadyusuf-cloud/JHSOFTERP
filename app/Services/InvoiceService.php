<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InvoiceService
{
    public function __construct(private readonly InventoryNumberGenerator $numberGenerator) {}

    /**
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function paginate(): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['customer', 'salesOrder', 'delivery'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id): Invoice
    {
        return Invoice::query()
            ->with(['customer', 'salesOrder', 'delivery', 'lines.item', 'payments'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFromDelivery(int $deliveryId, array $attributes): Invoice
    {
        return DB::transaction(function () use ($deliveryId, $attributes): Invoice {
            $delivery = Delivery::query()
                ->with(['salesOrder.customer', 'lines.salesOrderLine.item'])
                ->lockForUpdate()
                ->findOrFail($deliveryId);

            if ($delivery->status !== 'delivered' || $delivery->invoices()->exists()) {
                throw ValidationException::withMessages(['delivery' => 'Delivery belum selesai atau sudah memiliki invoice.']);
            }

            $invoice = Invoice::query()->create([
                'invoice_number' => $this->numberGenerator->next('INV', $attributes['invoice_date']),
                'sales_order_id' => $delivery->sales_order_id,
                'delivery_id' => $delivery->id,
                'customer_id' => $delivery->salesOrder->customer_id,
                'invoice_date' => $attributes['invoice_date'],
                'due_date' => $attributes['due_date'] ?? null,
                'status' => 'open',
                'total_amount' => '0.00',
                'paid_amount' => '0.00',
                'notes' => $attributes['notes'] ?? null,
            ]);

            $totalCents = 0;
            foreach ($delivery->lines as $deliveryLine) {
                $salesOrderLine = $deliveryLine->salesOrderLine;
                $amountCents = (int) round((float) $deliveryLine->qty * (float) $salesOrderLine->unit_price * 100);
                $totalCents += $amountCents;

                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'item_id' => $deliveryLine->item_id,
                    'description' => $salesOrderLine->item->name,
                    'qty' => $deliveryLine->qty,
                    'unit_price' => $salesOrderLine->unit_price,
                    'amount' => number_format($amountCents / 100, 2, '.', ''),
                ]);
            }

            $invoice->update(['total_amount' => number_format($totalCents / 100, 2, '.', '')]);

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordPayment(int $invoiceId, array $attributes): Payment
    {
        return DB::transaction(function () use ($invoiceId, $attributes): Payment {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
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

            return $payment;
        });
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
