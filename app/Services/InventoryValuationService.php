<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryTransaction;

final class InventoryValuationService
{
    public function averageUnitCost(int $itemId): ?string
    {
        $quantity = 0.0;
        $value = 0.0;

        InventoryTransaction::query()
            ->where('item_id', $itemId)
            ->whereIn('transaction_type', ['in', 'out'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['transaction_type', 'qty', 'unit_cost'])
            ->each(function (InventoryTransaction $transaction) use (&$quantity, &$value): void {
                $qty = (float) $transaction->qty;
                $unitCost = (float) $transaction->unit_cost;

                if ($transaction->transaction_type === 'in') {
                    $quantity += $qty;
                    $value += $qty * $unitCost;

                    return;
                }

                $quantity -= $qty;
                $value -= $qty * $unitCost;
            });

        if ($quantity <= 0.0000001 || $value <= 0) {
            return null;
        }

        return number_format($value / $quantity, 4, '.', '');
    }
}
