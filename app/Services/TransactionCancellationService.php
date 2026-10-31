<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\FinishedGoodsReceipt;
use App\Models\GeneralJournalEntry;
use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\SalesReturnLine;
use App\Models\StockAdjustment;
use App\Models\StockTake;
use App\Models\TransactionCancellation;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransactionCancellationService
{
    public function __construct(
        private readonly StockLevelService $stockLevels,
        private readonly AccountingService $accounting,
    ) {}

    public function cancel(string $type, int $id, string $reason, int $userId): TransactionCancellation
    {
        return DB::transaction(function () use ($type, $id, $reason, $userId): TransactionCancellation {
            $transaction = $this->findTransaction($type, $id);
            $this->ensureCanCancel($type, $transaction);
            if (TransactionCancellation::query()
                ->where('transaction_type', $type)
                ->where('transaction_id', $transaction->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi ini sudah pernah dibatalkan.',
                ]);
            }

            $cancellation = TransactionCancellation::query()->create([
                'transaction_type' => $type,
                'transaction_id' => $transaction->getKey(),
                'reason' => $reason,
                'cancelled_by' => $userId,
            ]);

            $journalSources = [];
            $inventoryTransactions = collect();

            switch ($type) {
                case 'sales-order':
                    $transaction->update(['status' => 'cancelled']);
                    break;
                case 'purchase-order':
                    $transaction->update(['status' => 'cancelled']);
                    break;
                case 'delivery':
                    $inventoryTransactions = InventoryTransaction::query()
                        ->where('reference_type', DeliveryLine::class)
                        ->whereIn('reference_id', $transaction->lines()->pluck('id'))
                        ->get();
                    $journalSources[] = $transaction;
                    $transaction->update(['status' => 'cancelled']);
                    break;
                case 'receiving':
                    $inventoryTransactions = InventoryTransaction::query()
                        ->where('reference_type', GoodsReceipt::class)
                        ->where('reference_id', $transaction->id)
                        ->get();
                    $journalSources[] = $transaction;
                    foreach ($transaction->lines as $line) {
                        $orderLine = $line->purchaseOrderLine()->lockForUpdate()->firstOrFail();
                        $received = (float) $orderLine->qty_received - (float) $line->qty;
                        if ($received < -0.0000001) {
                            throw ValidationException::withMessages([
                                'transaction' => 'Penerimaan tidak dapat dibatalkan karena kuantitas PO sudah tidak konsisten.',
                            ]);
                        }
                        $orderLine->update(['qty_received' => number_format(max(0, $received), 4, '.', '')]);
                    }
                    $order = $transaction->purchaseOrder()->lockForUpdate()->firstOrFail();
                    $hasRemainingLines = $order->lines()->whereColumn('qty_received', '<', 'qty')->exists();
                    $hasAnyReceipt = (float) $order->lines()->sum('qty_received') > 0;
                    $order->update(['status' => $hasRemainingLines ? ($hasAnyReceipt ? 'partial' : 'open') : 'received']);
                    $transaction->update(['status' => 'cancelled']);
                    break;
                case 'invoice':
                    foreach ($transaction->payments()->lockForUpdate()->get() as $payment) {
                        TransactionCancellation::query()->create([
                            'transaction_type' => 'payment',
                            'transaction_id' => $payment->id,
                            'reason' => 'Pembayaran dibatalkan bersama invoice: '.$reason,
                            'cancelled_by' => $userId,
                        ]);
                        $journalSources[] = $payment;
                        $payment->delete();
                    }
                    $journalSources[] = $transaction;
                    $transaction->update(['status' => 'cancelled']);
                    $transaction->delete();
                    $this->reverseJournals($journalSources, $cancellation);

                    return $cancellation;
                case 'payment':
                    $invoice = $transaction->invoice()->lockForUpdate()->firstOrFail();
                    $journalSources[] = $transaction;
                    $transaction->delete();
                    $paid = max(0, (float) $invoice->paid_amount - (float) $transaction->amount);
                    $invoice->update([
                        'paid_amount' => number_format($paid, 2, '.', ''),
                        'status' => $paid <= 0 ? 'open' : ($paid + 0.0000001 >= (float) $invoice->total_amount ? 'paid' : 'partial'),
                    ]);
                    break;
                case 'sales-return':
                case 'purchase-return':
                    $lineClass = $type === 'sales-return'
                        ? SalesReturnLine::class
                        : PurchaseReturnLine::class;
                    $inventoryTransactions = InventoryTransaction::query()
                        ->where('reference_type', $lineClass)
                        ->whereIn('reference_id', $transaction->lines()->pluck('id'))
                        ->get();
                    break;
                case 'stock-adjustment':
                case 'stock-take':
                    $inventoryTransactions = $transaction->inventoryTransactions()->get();
                    $journalSources[] = $transaction;
                    break;
                case 'work-order':
                    $materialIds = $transaction->materials()->pluck('id');
                    $inventoryTransactions = InventoryTransaction::query()
                        ->where(function ($query) use ($materialIds, $transaction): void {
                            $query->where(function ($query) use ($materialIds): void {
                                $query->where('reference_type', WorkOrderMaterial::class)
                                    ->whereIn('reference_id', $materialIds);
                            })->orWhere(function ($query) use ($transaction): void {
                                $query->where('reference_type', FinishedGoodsReceipt::class)
                                    ->whereIn('reference_id', $transaction->finishedGoodsReceipts()->pluck('id'));
                            });
                        })
                        ->get();
                    $journalSources = $inventoryTransactions->all();
                    $transaction->update(['status' => 'cancelled']);
                    break;
                case 'journal':
                    $journalSources[] = $transaction;
                    break;
            }

            $this->reverseInventory($inventoryTransactions, $cancellation);
            $this->reverseJournals($journalSources, $cancellation);

            if ($type !== 'journal') {
                $transaction->delete();
            }

            return $cancellation;
        });
    }

    private function findTransaction(string $type, int $id): Model
    {
        $model = match ($type) {
            'sales-order' => SalesOrder::class,
            'purchase-order' => PurchaseOrder::class,
            'delivery' => Delivery::class,
            'receiving' => GoodsReceipt::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'sales-return' => SalesReturn::class,
            'purchase-return' => PurchaseReturn::class,
            'stock-adjustment' => StockAdjustment::class,
            'stock-take' => StockTake::class,
            'work-order' => WorkOrder::class,
            'journal' => GeneralJournalEntry::class,
            default => abort(404),
        };

        return $model::query()->lockForUpdate()->findOrFail($id);
    }

    private function ensureCanCancel(string $type, Model $transaction): void
    {
        $hasActiveDependents = match ($type) {
            'sales-order' => $transaction->deliveries()->exists()
                || $transaction->workOrders()->exists()
                || $transaction->invoices()->exists(),
            'purchase-order' => $transaction->goodsReceipts()->exists(),
            'delivery' => $transaction->invoices()->exists()
                || SalesReturn::query()->where('delivery_id', $transaction->id)->exists(),
            'receiving' => PurchaseReturn::query()->where('goods_receipt_id', $transaction->id)->exists(),
            'journal' => $transaction->source_type !== null,
            default => false,
        };

        if ($hasActiveDependents) {
            throw ValidationException::withMessages([
                'transaction' => 'Batalkan terlebih dahulu dokumen lanjutan yang masih aktif sebelum membatalkan transaksi ini.',
            ]);
        }
    }

    private function reverseInventory(iterable $transactions, TransactionCancellation $cancellation): void
    {
        foreach ($transactions as $transaction) {
            $direction = $transaction->transaction_type === 'in' ? 'out' : 'in';
            if ($direction === 'in') {
                $this->stockLevels->increase(
                    (int) $transaction->item_id,
                    (int) $transaction->warehouse_id,
                    $transaction->location_id === null ? null : (int) $transaction->location_id,
                    (string) $transaction->qty,
                );
            } else {
                $this->stockLevels->decrease(
                    (int) $transaction->item_id,
                    (int) $transaction->warehouse_id,
                    $transaction->location_id === null ? null : (int) $transaction->location_id,
                    (string) $transaction->qty,
                    'transaction',
                );
            }

            InventoryTransaction::query()->create([
                'transaction_date' => now(),
                'item_id' => $transaction->item_id,
                'warehouse_id' => $transaction->warehouse_id,
                'location_id' => $transaction->location_id,
                'transaction_type' => $direction,
                'qty' => $transaction->qty,
                'unit_cost' => $transaction->unit_cost,
                'reference_type' => TransactionCancellation::class,
                'reference_id' => $cancellation->id,
                'notes' => "Pembalikan transaksi {$transaction->reference_type} #{$transaction->reference_id}",
            ]);
        }
    }

    /**
     * @param  iterable<Model>  $sources
     */
    private function reverseJournals(iterable $sources, TransactionCancellation $cancellation): void
    {
        $sourceKeys = [];
        $entries = collect();
        foreach ($sources as $source) {
            if ($source instanceof GeneralJournalEntry) {
                $entries->push($source->load('lines.account'));
            } else {
                $sourceKeys[] = [$source::class, $source->getKey()];
            }
        }
        if ($sourceKeys !== []) {
            $entries = $entries->merge(GeneralJournalEntry::query()
                ->with('lines.account')
                ->where(function ($query) use ($sourceKeys): void {
                    foreach ($sourceKeys as [$sourceType, $sourceId]) {
                        $query->orWhere(fn ($query) => $query
                            ->where('source_type', $sourceType)
                            ->where('source_id', $sourceId));
                    }
                })
                ->get());
        }
        if ($entries->isEmpty()) {
            return;
        }

        $lines = [];
        foreach ($entries as $entry) {
            foreach ($entry->lines as $line) {
                $lines[] = [
                    'account_code' => $line->account->code,
                    'debit_cents' => AccountingService::cents((string) $line->credit_amount),
                    'credit_cents' => AccountingService::cents((string) $line->debit_amount),
                    'description' => 'Pembalikan '.$entry->journal_number,
                ];
            }
        }

        $this->accounting->post(
            $cancellation,
            now()->toDateString(),
            'Pembatalan '.$cancellation->transaction_type.' #'.$cancellation->transaction_id,
            'Jurnal pembalik transaksi yang dibatalkan: '.$cancellation->reason,
            $lines,
            $cancellation->cancelled_by,
        );
    }
}
