<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use App\Models\SalesOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SalesOrderService
{
    public function __construct(
        private readonly MasterPriceService $prices,
        private readonly SalesOrderNumberGenerator $numberGenerator,
    ) {}

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
                'so_number' => $this->numberGenerator->next($attributes['order_date']),
                'customer_id' => $attributes['customer_id'],
                'order_date' => $attributes['order_date'],
                'status' => 'draft',
                'total_amount' => '0.00',
                'notes' => $attributes['notes'] ?? null,
            ]);

            $order->update([
                'total_amount' => $this->replaceLines($order, $attributes['lines'], $items, $attributes['order_date']),
            ]);

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
                'notes' => $attributes['notes'] ?? null,
            ]);

            $order->lines()->delete();
            $order->update([
                'total_amount' => $this->replaceLines($order, $attributes['lines'], $items, $attributes['order_date']),
            ]);

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
     * @param  array<int, array{item_id: int|string, qty: int|float|string}>  $lines
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
     * @param  array<int, array{item_id: int|string, qty: int|float|string}>  $lines
     */
    private function replaceLines(SalesOrder $order, array $lines, Collection $items, string $orderDate): string
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $item = $items->get((int) $line['item_id']);
            $masterPrice = $this->prices->priceForDate((int) $item->id, $orderDate, lock: true);

            if ($masterPrice === null) {
                throw ValidationException::withMessages([
                    'lines' => "Harga {$item->code} belum tersedia pada tanggal Sales Order.",
                ]);
            }

            $lineTotal = round((float) $line['qty'] * (float) $masterPrice->price, 2);
            $total += $lineTotal;

            $order->lines()->create([
                'item_id' => $item->id,
                'qty' => $line['qty'],
                'unit_price' => $masterPrice->price,
                'unit_id' => $item->unit_id,
                'master_price_id' => $masterPrice->id,
            ]);
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
