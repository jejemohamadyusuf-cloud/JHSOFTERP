<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use App\Models\MasterPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MasterPriceService
{
    /**
     * @return LengthAwarePaginator<int, MasterPrice>
     */
    public function paginate(): LengthAwarePaginator
    {
        return MasterPrice::query()
            ->with('item')
            ->latest('start_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id): MasterPrice
    {
        return MasterPrice::query()->with('item')->findOrFail($id);
    }

    public function priceForDate(int $itemId, string $date, bool $lock = false, string $type = 'sell'): ?MasterPrice
    {
        $query = MasterPrice::query()
            ->where('item_id', $itemId)
            ->where('price_type', $type)
            ->whereDate('start_date', '<=', $date)
            ->where(fn ($query) => $query
                ->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $date))
            ->latest('start_date');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @param  array{item_id: int|string, price: int|float|string, start_date: string, end_date?: ?string}  $attributes
     */
    public function create(array $attributes): MasterPrice
    {
        return DB::transaction(function () use ($attributes): MasterPrice {
            $attributes['price_type'] ??= 'sell';
            Item::query()->lockForUpdate()->findOrFail((int) $attributes['item_id']);
            $this->ensureNoOverlap($attributes);

            return MasterPrice::query()->create($attributes);
        });
    }

    /**
     * @param  array{item_id: int|string, price: int|float|string, start_date: string, end_date?: ?string}  $attributes
     */
    public function update(int $id, array $attributes): MasterPrice
    {
        return DB::transaction(function () use ($id, $attributes): MasterPrice {
            $price = MasterPrice::query()->lockForUpdate()->findOrFail($id);
            $attributes['price_type'] ??= $price->price_type;
            Item::query()->lockForUpdate()->findOrFail((int) $attributes['item_id']);

            if (($price->salesOrderLines()->exists() || $price->purchaseOrderLines()->exists())
                && (
                    (int) $attributes['item_id'] !== $price->item_id
                    || $attributes['price_type'] !== $price->price_type
                    || $this->normalizeDecimal((string) $attributes['price']) !== $this->normalizeDecimal((string) $price->getRawOriginal('price'))
                    || $attributes['start_date'] !== $price->start_date->toDateString()
                )) {
                throw ValidationException::withMessages([
                    'price' => 'Harga, jenis, barang, dan tanggal mulai tidak dapat diubah karena sudah dipakai transaksi. Ubah tanggal akhirnya atau tambahkan harga baru.',
                ]);
            }

            $this->ensureNoOverlap($attributes, $price->id);
            $price->update($attributes);

            return $price;
        });
    }

    public function delete(int $id): void
    {
        $price = $this->find($id);

        if ($price->salesOrderLines()->exists() || $price->purchaseOrderLines()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Harga ini sudah digunakan oleh transaksi dan tidak dapat dihapus.',
            ]);
        }

        try {
            $price->delete();
        } catch (QueryException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'delete' => 'Harga tidak dapat dihapus karena sudah digunakan oleh data lain.',
            ]);
        }
    }

    /**
     * @param  array{item_id: int|string, price: int|float|string, start_date: string, end_date?: ?string}  $attributes
     */
    private function ensureNoOverlap(array $attributes, ?int $ignoreId = null): void
    {
        $overlap = MasterPrice::query()
            ->where('item_id', (int) $attributes['item_id'])
            ->where('price_type', $attributes['price_type'] ?? 'sell')
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->when(
                filled($attributes['end_date'] ?? null),
                fn ($query) => $query->whereDate('start_date', '<=', $attributes['end_date']),
            )
            ->where(fn ($query) => $query
                ->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $attributes['start_date']))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Periode harga bertumpuk. Tutup harga sebelumnya paling lambat sehari sebelum tanggal mulai harga baru.',
            ]);
        }
    }

    private function normalizeDecimal(string $value): string
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = rtrim($fraction, '0');

        return $fraction === '' ? $whole : $whole.'.'.$fraction;
    }
}
