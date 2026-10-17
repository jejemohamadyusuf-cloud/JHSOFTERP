<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use App\Models\SalesOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SalesOrderService
{
    /**
     * @return LengthAwarePaginator<int, SalesOrder>
     */
    public function paginate(): LengthAwarePaginator
    {
        return SalesOrder::query()
            ->with('customer')
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Item>
     */
    public function availableItems(): Collection
    {
        return Item::query()
            ->with('unit')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): SalesOrder
    {
        return SalesOrder::query()
            ->with(['customer', 'lines.item.unit', 'lines.unit'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SalesOrder
    {
        return DB::transaction(function () use ($attributes): SalesOrder {
            $items = $this->itemsForLines($attributes['lines']);
            $order = SalesOrder::query()->create([
                'so_number' => 'SO-'.now()->format('Ymd').'-'.Str::ulid(),
                'customer_id' => $attributes['customer_id'],
                'order_date' => $attributes['order_date'],
                'status' => 'draft',
                'total_amount' => $this->totalAmount($attributes['lines']),
                'notes' => $attributes['notes'] ?? null,
            ]);

            $this->replaceLines($order, $attributes['lines'], $items);

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDraft(int $id, array $attributes): SalesOrder
    {
        return DB::transaction(function () use ($id, $attributes): SalesOrder {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($id);
            $this->ensureDraft($order);
            $items = $this->itemsForLines($attributes['lines']);

            $order->update([
                'customer_id' => $attributes['customer_id'],
                'order_date' => $attributes['order_date'],
                'total_amount' => $this->totalAmount($attributes['lines']),
                'notes' => $attributes['notes'] ?? null,
            ]);

            $order->lines()->delete();
            $this->replaceLines($order, $attributes['lines'], $items);

            return $order;
        });
    }

    public function confirm(int $id): SalesOrder
    {
        return DB::transaction(function () use ($id): SalesOrder {
            $order = SalesOrder::query()->lockForUpdate()->withCount('lines')->findOrFail($id);
            $this->ensureDraft($order);

            if ($order->lines_count === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan tanpa barang tidak dapat dikonfirmasi.',
                ]);
            }

            $order->update(['status' => 'confirmed']);

            return $order;
        });
    }

    /**
     * @param  array<int, array{item_id: int|string, qty: int|float|string, unit_price: int|float|string}>  $lines
     * @return Collection<int, Item>
     */
    private function itemsForLines(array $lines): Collection
    {
        $items = Item::query()
            ->whereIn('id', collect($lines)->pluck('item_id')->unique())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($items->count() !== collect($lines)->pluck('item_id')->unique()->count()) {
            throw ValidationException::withMessages([
                'lines' => 'Salah satu barang sudah tidak aktif atau tidak tersedia.',
            ]);
        }

        return $items;
    }

    /**
     * @param  array<int, array{item_id: int|string, qty: int|float|string, unit_price: int|float|string}>  $lines
     */
    private function replaceLines(SalesOrder $order, array $lines, Collection $items): void
    {
        foreach ($lines as $line) {
            $item = $items->get((int) $line['item_id']);

            $order->lines()->create([
                'item_id' => $item->id,
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'],
                'unit_id' => $item->unit_id,
            ]);
        }
    }

    /**
     * @param  array<int, array{qty: int|float|string, unit_price: int|float|string}>  $lines
     */
    private function totalAmount(array $lines): string
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $total += round((float) $line['qty'] * (float) $line['unit_price'], 2);
        }

        return number_format($total, 2, '.', '');
    }

    private function ensureDraft(SalesOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya Sales Order berstatus draft yang dapat diubah atau dikonfirmasi.',
            ]);
        }
    }
}
