<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\StockTake;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StockService
{
    public function __construct(
        private readonly InventoryNumberGenerator $numberGenerator,
        private readonly StockLevelService $stockLevels,
        private readonly AccountingService $accounting,
        private readonly InventoryValuationService $valuation,
    ) {}

    /**
     * @return LengthAwarePaginator<int, StockLevel>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return StockLevel::query()
            ->with(['item.unit', 'item.unitConversions', 'warehouse', 'location'])
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->whereHas('item', fn ($items) => $items
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('warehouse', fn ($warehouses) => $warehouses
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            })
            ->orderBy('item_id')
            ->orderBy('warehouse_id')
            ->orderBy('location_id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Item>
     */
    public function activeItems(): Collection
    {
        return Item::query()->with(['unit', 'unitConversions'])->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Warehouse>
     */
    public function activeWarehouses(): Collection
    {
        return Warehouse::query()
            ->with(['locations' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{system_qty: string, reserved_qty: string, formatted_qty: string, base_unit: string, conversions: array<int, array{code: string, base_units: int}>}
     */
    public function stockTakePosition(int $itemId, int $warehouseId, ?int $locationId): array
    {
        $item = Item::query()->with(['unit', 'unitConversions'])->findOrFail($itemId);
        $query = StockLevel::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId);
        $locationId === null ? $query->whereNull('location_id') : $query->where('location_id', $locationId);
        $level = $query->first();
        $systemQty = (string) ($level?->qty_on_hand ?? '0');

        return [
            'system_qty' => $systemQty,
            'reserved_qty' => (string) ($level?->qty_reserved ?? '0'),
            'formatted_qty' => $item->formatStockQuantity($systemQty),
            'base_unit' => $item->unit->code,
            'conversions' => $item->unitConversions
                ->map(fn ($conversion): array => ['code' => $conversion->unit->code, 'base_units' => $conversion->base_units])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createStockTake(array $attributes): StockTake
    {
        return DB::transaction(function () use ($attributes): StockTake {
            $stockTake = StockTake::query()->create([
                'stock_take_number' => $this->numberGenerator->next('ST', $attributes['stock_take_date']),
                'stock_take_date' => $attributes['stock_take_date'],
                'notes' => $attributes['notes'] ?? null,
            ]);
            $journalLines = [];

            foreach ($attributes['lines'] as $index => $line) {
                $item = Item::query()
                    ->whereKey($line['item_id'])
                    ->where('is_active', true)
                    ->firstOrFail();
                $warehouse = Warehouse::query()
                    ->whereKey($line['warehouse_id'])
                    ->where('is_active', true)
                    ->firstOrFail();
                $locationId = ($line['location_id'] ?? '') === '' ? null : (int) $line['location_id'];

                if ($locationId !== null && ! Location::query()
                    ->whereKey($locationId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.location_id" => 'Lokasi harus aktif dan berada di gudang yang dipilih.',
                    ]);
                }

                $levelQuery = StockLevel::query()
                    ->where('item_id', $item->id)
                    ->where('warehouse_id', $warehouse->id);
                $locationId === null
                    ? $levelQuery->whereNull('location_id')
                    : $levelQuery->where('location_id', $locationId);
                $level = $levelQuery->lockForUpdate()->first();
                $systemQty = (float) ($level?->qty_on_hand ?? 0);
                $physicalQty = (float) $line['physical_qty'];
                $reservedQty = (float) ($level?->qty_reserved ?? 0);
                if (abs($systemQty - (float) $line['system_qty']) >= 0.0001) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.physical_qty" => 'Saldo sistem berubah setelah hitungan dibuat. Muat ulang saldo dan hitung kembali barang ini.',
                    ]);
                }
                if ($physicalQty + 0.0000001 < $reservedQty) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.physical_qty" => 'Stok fisik tidak boleh lebih kecil dari jumlah stok yang sudah dicadangkan.',
                    ]);
                }

                $discrepancy = round($physicalQty - $systemQty, 4);
                $stockTake->lines()->create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'system_qty' => number_format($systemQty, 2, '.', ''),
                    'physical_qty' => number_format($physicalQty, 2, '.', ''),
                    'discrepancy_qty' => number_format($discrepancy, 2, '.', ''),
                ]);

                if (abs($discrepancy) < 0.0001) {
                    continue;
                }

                $direction = $discrepancy > 0 ? 'in' : 'out';
                $quantity = number_format(abs($discrepancy), 2, '.', '');
                $unitCost = $this->valuation->averageUnitCost((int) $item->id) ?? '0.0000';
                $amountCents = AccountingService::valueCents($quantity, $unitCost);
                if ($direction === 'in') {
                    $this->stockLevels->increase($item->id, $warehouse->id, $locationId, $quantity);
                } else {
                    $this->stockLevels->decrease($item->id, $warehouse->id, $locationId, $quantity, "lines.{$index}.physical_qty");
                }

                InventoryTransaction::query()->create([
                    'transaction_date' => $attributes['stock_take_date'].' 00:00:00',
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'transaction_type' => $direction,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'reference_type' => StockTake::class,
                    'reference_id' => $stockTake->id,
                    'notes' => $attributes['notes'] ?? null,
                ]);

                $inventoryAccount = $this->accounting->inventoryAccountCode($item->item_type);
                $journalLines[] = [
                    'account_code' => $direction === 'in' ? $inventoryAccount : '6400',
                    'debit_cents' => $amountCents,
                    'credit_cents' => 0,
                    'description' => $direction === 'in'
                        ? "Koreksi stok masuk {$item->name}"
                        : "Selisih stok kurang {$item->name}",
                ];
                $journalLines[] = [
                    'account_code' => $direction === 'in' ? '6400' : $inventoryAccount,
                    'debit_cents' => 0,
                    'credit_cents' => $amountCents,
                    'description' => $direction === 'in'
                        ? "Koreksi selisih stok {$item->name}"
                        : "Pengurangan persediaan {$item->name}",
                ];
            }

            $this->accounting->post($stockTake, $stockTake->stock_take_date->toDateString(), $stockTake->stock_take_number, "Penyesuaian hasil stock take {$stockTake->stock_take_number}", $journalLines);

            return $stockTake;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createAdjustment(string $direction, array $attributes): StockAdjustment
    {
        if (! in_array($direction, ['in', 'out'], true)) {
            throw new \InvalidArgumentException('Arah penyesuaian stok tidak valid.');
        }

        return DB::transaction(function () use ($direction, $attributes): StockAdjustment {
            $adjustment = StockAdjustment::query()->create([
                'adjustment_number' => $this->numberGenerator->next($direction === 'in' ? 'AI' : 'AO', $attributes['adjustment_date']),
                'direction' => $direction,
                'adjustment_date' => $attributes['adjustment_date'],
                'notes' => $attributes['notes'] ?? null,
            ]);
            $journalLines = [];

            foreach ($attributes['lines'] as $index => $line) {
                $item = Item::query()
                    ->whereKey($line['item_id'])
                    ->where('is_active', true)
                    ->firstOrFail();
                $warehouse = Warehouse::query()
                    ->whereKey($line['warehouse_id'])
                    ->where('is_active', true)
                    ->firstOrFail();
                $locationId = $line['location_id'] ?? null;

                if ($locationId !== null && ! Location::query()
                    ->whereKey($locationId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'lines' => 'Lokasi harus aktif dan berada di gudang yang dipilih.',
                    ]);
                }

                $qty = (string) $line['qty'];
                $unitCost = $direction === 'in'
                    ? (string) ($line['unit_cost'] ?? '0')
                    : ($this->valuation->averageUnitCost((int) $item->id) ?? '0.0000');
                $amountCents = AccountingService::valueCents($qty, $unitCost);
                $adjustment->lines()->create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                if ($direction === 'in') {
                    $this->stockLevels->increase($item->id, $warehouse->id, $locationId, $qty);
                } else {
                    $this->stockLevels->decrease($item->id, $warehouse->id, $locationId, $qty, "lines.{$index}.qty");
                }

                InventoryTransaction::query()->create([
                    'transaction_date' => $attributes['adjustment_date'].' 00:00:00',
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'transaction_type' => $direction,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'notes' => $attributes['notes'] ?? null,
                ]);

                $inventoryAccount = $this->accounting->inventoryAccountCode($item->item_type);
                $journalLines[] = [
                    'account_code' => $direction === 'in' ? $inventoryAccount : '6400',
                    'debit_cents' => $amountCents,
                    'credit_cents' => 0,
                    'description' => $direction === 'in'
                        ? "Adjustment masuk {$item->name}"
                        : "Selisih kurang persediaan {$item->name}",
                ];
                $journalLines[] = [
                    'account_code' => $direction === 'in' ? '6400' : $inventoryAccount,
                    'debit_cents' => 0,
                    'credit_cents' => $amountCents,
                    'description' => $direction === 'in'
                        ? "Koreksi nilai persediaan {$item->name}"
                        : "Adjustment keluar {$item->name}",
                ];
            }

            $this->accounting->post($adjustment, $adjustment->adjustment_date->toDateString(), $adjustment->adjustment_number, "Penyesuaian stok {$adjustment->adjustment_number}", $journalLines);

            return $adjustment;
        });
    }
}
