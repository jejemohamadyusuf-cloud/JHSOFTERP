<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryTransaction;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReturnService
{
    public function __construct(
        private readonly InventoryNumberGenerator $numbers,
        private readonly StockLevelService $stock,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SalesReturn>
     */
    public function paginateSales(): LengthAwarePaginator
    {
        return SalesReturn::query()
            ->with(['delivery.salesOrder.customer'])
            ->withCount('lines')
            ->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, PurchaseReturn>
     */
    public function paginatePurchases(): LengthAwarePaginator
    {
        return PurchaseReturn::query()
            ->with(['goodsReceipt.purchaseOrder.supplier'])
            ->withCount('lines')
            ->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Delivery>
     */
    public function salesSources(): Collection
    {
        return Delivery::query()
            ->where('status', 'delivered')
            ->with(['salesOrder.customer', 'warehouse', 'lines.item.unit', 'lines.location', 'lines.salesReturnLines'])
            ->latest('delivery_date')
            ->get();
    }

    /**
     * @return Collection<int, GoodsReceipt>
     */
    public function purchaseSources(): Collection
    {
        return GoodsReceipt::query()
            ->where('status', 'posted')
            ->with(['purchaseOrder.supplier', 'warehouse', 'lines.item.unit', 'lines.location', 'lines.purchaseReturnLines'])
            ->latest('receipt_date')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createSales(array $attributes): SalesReturn
    {
        return DB::transaction(function () use ($attributes): SalesReturn {
            $delivery = Delivery::query()->lockForUpdate()->findOrFail($attributes['source_id']);
            if ($delivery->status !== 'delivered') {
                throw ValidationException::withMessages(['source_id' => 'Retur hanya dapat dibuat dari delivery yang sudah diposting.']);
            }
            if ($attributes['return_date'] < $delivery->delivery_date->toDateString()) {
                throw ValidationException::withMessages(['return_date' => 'Tanggal retur tidak boleh sebelum tanggal delivery.']);
            }

            $lines = $this->requestedSourceLines($attributes['lines'], DeliveryLine::class, $delivery->id, 'delivery_id');
            $return = SalesReturn::query()->create([
                'return_number' => $this->numbers->next('SR', $attributes['return_date']),
                'delivery_id' => $delivery->id,
                'return_date' => $attributes['return_date'],
                'reason' => $attributes['reason'],
                'notes' => $attributes['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as [$sourceLine, $requested, $index]) {
                $alreadyReturned = (float) $sourceLine->salesReturnLines()->sum('qty');
                if ($alreadyReturned + (float) $requested['qty'] > (float) $sourceLine->qty + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.qty" => 'Kuantitas retur melebihi sisa barang pada delivery.',
                    ]);
                }

                $unitCost = InventoryTransaction::query()
                    ->where('reference_type', DeliveryLine::class)
                    ->where('reference_id', $sourceLine->id)
                    ->where('transaction_type', 'out')
                    ->value('unit_cost');
                if ($unitCost === null) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.qty" => 'Biaya persediaan delivery tidak ditemukan; retur belum dapat diproses.',
                    ]);
                }

                $returnLine = $return->lines()->create([
                    'delivery_line_id' => $sourceLine->id,
                    'item_id' => $sourceLine->item_id,
                    'location_id' => $sourceLine->location_id,
                    'qty' => $requested['qty'],
                    'unit_cost' => $unitCost,
                ]);
                $this->stock->increase(
                    (int) $sourceLine->item_id,
                    (int) $delivery->warehouse_id,
                    $sourceLine->location_id,
                    (string) $requested['qty'],
                );
                $this->recordInventory($attributes['return_date'], $returnLine, (int) $delivery->warehouse_id, 'in', $return->return_number);
            }

            return $return;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createPurchase(array $attributes): PurchaseReturn
    {
        return DB::transaction(function () use ($attributes): PurchaseReturn {
            $receipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($attributes['source_id']);
            if ($receipt->status !== 'posted') {
                throw ValidationException::withMessages(['source_id' => 'Retur hanya dapat dibuat dari receiving yang sudah diposting.']);
            }
            if ($attributes['return_date'] < $receipt->receipt_date->toDateString()) {
                throw ValidationException::withMessages(['return_date' => 'Tanggal retur tidak boleh sebelum tanggal receiving.']);
            }

            $lines = $this->requestedSourceLines($attributes['lines'], GoodsReceiptLine::class, $receipt->id, 'goods_receipt_id');
            $return = PurchaseReturn::query()->create([
                'return_number' => $this->numbers->next('PR', $attributes['return_date']),
                'goods_receipt_id' => $receipt->id,
                'return_date' => $attributes['return_date'],
                'reason' => $attributes['reason'],
                'notes' => $attributes['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as [$sourceLine, $requested, $index]) {
                $alreadyReturned = (float) $sourceLine->purchaseReturnLines()->sum('qty');
                if ($alreadyReturned + (float) $requested['qty'] > (float) $sourceLine->qty + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.qty" => 'Kuantitas retur melebihi sisa barang pada receiving.',
                    ]);
                }

                $returnLine = $return->lines()->create([
                    'goods_receipt_line_id' => $sourceLine->id,
                    'item_id' => $sourceLine->item_id,
                    'location_id' => $sourceLine->location_id,
                    'qty' => $requested['qty'],
                    'unit_cost' => $sourceLine->unit_cost,
                ]);
                $this->stock->decrease(
                    (int) $sourceLine->item_id,
                    (int) $receipt->warehouse_id,
                    $sourceLine->location_id,
                    (string) $requested['qty'],
                    "lines.{$index}.qty",
                );
                $this->recordInventory($attributes['return_date'], $returnLine, (int) $receipt->warehouse_id, 'out', $return->return_number);
            }

            return $return;
        });
    }

    /**
     * @param  array<int, array{source_line_id: int|string, qty: int|float|string}>  $requested
     * @return list<array{0: DeliveryLine|GoodsReceiptLine, 1: array{source_line_id: int|string, qty: int|float|string}, 2: int}>
     */
    private function requestedSourceLines(array $requested, string $model, int $sourceId, string $foreignKey): array
    {
        $ids = collect($requested)->pluck('source_line_id')->map(fn ($id): int => (int) $id);
        $sourceLines = $model::query()
            ->where($foreignKey, $sourceId)
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($sourceLines->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages(['lines' => 'Baris barang harus berasal dari dokumen sumber yang dipilih.']);
        }

        return collect($requested)->map(function (array $line, int $index) use ($sourceLines): array {
            return [$sourceLines->get((int) $line['source_line_id']), $line, $index];
        })->all();
    }

    private function recordInventory(string $date, object $line, int $warehouseId, string $direction, string $number): void
    {
        InventoryTransaction::query()->create([
            'transaction_date' => $date.' 00:00:00',
            'item_id' => $line->item_id,
            'warehouse_id' => $warehouseId,
            'location_id' => $line->location_id,
            'transaction_type' => $direction,
            'qty' => $line->qty,
            'unit_cost' => $line->unit_cost,
            'reference_type' => $line::class,
            'reference_id' => $line->id,
            'notes' => "Retur {$number}",
        ]);
    }
}
