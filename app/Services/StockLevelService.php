<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StockLevel;
use Illuminate\Validation\ValidationException;

final class StockLevelService
{
    public function increase(int $itemId, int $warehouseId, ?int $locationId, string $qty): void
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

    public function decrease(
        int $itemId,
        int $warehouseId,
        ?int $locationId,
        string $qty,
        string $errorKey = 'qty',
    ): void {
        $query = StockLevel::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId);
        $locationId === null
            ? $query->whereNull('location_id')
            : $query->where('location_id', $locationId);

        $level = $query->lockForUpdate()->first();
        if ($level === null || (float) $level->qty_available + 0.0000001 < (float) $qty) {
            throw ValidationException::withMessages([
                $errorKey => 'Kuantitas melebihi stok tersedia pada gudang dan lokasi yang dipilih.',
            ]);
        }

        $onHand = (float) $level->qty_on_hand - (float) $qty;
        $level->update([
            'qty_on_hand' => number_format($onHand, 4, '.', ''),
            'qty_available' => number_format($onHand - (float) $level->qty_reserved, 4, '.', ''),
        ]);
    }
}
