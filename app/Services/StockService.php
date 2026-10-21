<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StockService
{
    public function __construct(private readonly InventoryNumberGenerator $numberGenerator) {}

    /**
     * @return LengthAwarePaginator<int, StockLevel>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return StockLevel::query()
            ->with(['item.unit', 'warehouse', 'location'])
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
        return Item::query()->with('unit')->where('is_active', true)->orderBy('name')->get();
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
     * @return Collection<int, StockAdjustment>
     */
    public function recentAdjustments(): Collection
    {
        return StockAdjustment::query()->withCount('lines')->latest('id')->limit(10)->get();
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
                $unitCost = (string) ($line['unit_cost'] ?? '0');
                $adjustment->lines()->create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                if ($direction === 'in') {
                    $this->increaseStock($item->id, $warehouse->id, $locationId, $qty);
                } else {
                    $this->decreaseStock($item->id, $warehouse->id, $locationId, $qty, $index);
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
            }

            return $adjustment;
        });
    }

    private function decreaseStock(int $itemId, int $warehouseId, ?int $locationId, string $qty, int $lineIndex): void
    {
        $query = StockLevel::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId);
        $locationId === null
            ? $query->whereNull('location_id')
            : $query->where('location_id', $locationId);

        $level = $query->lockForUpdate()->first();
        if ($level === null || (float) $level->qty_available + 0.0000001 < (float) $qty) {
            throw ValidationException::withMessages([
                "lines.{$lineIndex}.qty" => 'Kuantitas penyesuaian keluar melebihi stok tersedia pada gudang dan lokasi yang dipilih.',
            ]);
        }

        $onHand = (float) $level->qty_on_hand - (float) $qty;
        $level->update([
            'qty_on_hand' => number_format($onHand, 4, '.', ''),
            'qty_available' => number_format($onHand - (float) $level->qty_reserved, 4, '.', ''),
        ]);
    }

    private function increaseStock(int $itemId, int $warehouseId, ?int $locationId, string $qty): void
    {
        $query = StockLevel::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId);
        $locationId === null
            ? $query->whereNull('location_id')
            : $query->where('location_id', $locationId);

        $level = $query->lockForUpdate()->first();
        if ($level === null) {
            StockLevel::query()->create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'qty_on_hand' => $qty,
                'qty_reserved' => '0',
                'qty_available' => $qty,
            ]);

            return;
        }

        $onHand = (float) $level->qty_on_hand + (float) $qty;
        $level->update([
            'qty_on_hand' => number_format($onHand, 4, '.', ''),
            'qty_available' => number_format($onHand - (float) $level->qty_reserved, 4, '.', ''),
        ]);
    }
}
