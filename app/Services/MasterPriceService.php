<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use App\Models\MasterPrice;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MasterPriceService
{
    /**
     * @return LengthAwarePaginator<int, MasterPrice>
     */
    public function paginate(string $type): LengthAwarePaginator
    {
        return MasterPrice::query()
            ->with(['item', 'supplier'])
            ->where('price_type', $type)
            ->latest('start_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id, ?string $type = null): MasterPrice
    {
        return MasterPrice::query()
            ->with(['item', 'supplier'])
            ->when($type !== null, fn ($query) => $query->where('price_type', $type))
            ->findOrFail($id);
    }

    public function priceForDate(
        int $itemId,
        string $date,
        bool $lock = false,
        string $type = 'sell',
        ?int $supplierId = null,
    ): ?MasterPrice {
        if ($type === 'buy' && $supplierId === null) {
            return null;
        }

        $query = MasterPrice::query()
            ->where('item_id', $itemId)
            ->where('price_type', $type)
            ->when(
                $type === 'buy',
                fn ($query) => $query->where('supplier_id', $supplierId),
                fn ($query) => $query->whereNull('supplier_id'),
            )
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

    public function buyPricesForDate(int $itemId, string $date): Collection
    {
        return MasterPrice::query()
            ->with('supplier')
            ->where('item_id', $itemId)
            ->where('price_type', 'buy')
            ->whereNotNull('supplier_id')
            ->whereHas('supplier', fn ($query) => $query->where('is_active', true))
            ->whereDate('start_date', '<=', $date)
            ->where(fn ($query) => $query
                ->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $date))
            ->orderBy('price')
            ->get();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): MasterPrice
    {
        return DB::transaction(function () use ($attributes): MasterPrice {
            $attributes['price_type'] ??= 'sell';
            $this->normalizeSupplier($attributes);
            Item::query()->lockForUpdate()->findOrFail((int) $attributes['item_id']);
            $this->ensureNoOverlap($attributes);

            return MasterPrice::query()->create($attributes);
        });
    }

    /**
     * @param  array{item_id: int|string, price: int|float|string, start_date: string, end_date?: ?string}  $attributes
     */
    public function update(int $id, array $attributes, ?string $expectedType = null): MasterPrice
    {
        return DB::transaction(function () use ($id, $attributes, $expectedType): MasterPrice {
            $price = MasterPrice::query()
                ->when($expectedType !== null, fn ($query) => $query->where('price_type', $expectedType))
                ->lockForUpdate()
                ->findOrFail($id);
            $attributes['price_type'] ??= $price->price_type;
            $this->normalizeSupplier($attributes);
            Item::query()->lockForUpdate()->findOrFail((int) $attributes['item_id']);

            if (($price->salesOrderLines()->exists() || $price->purchaseOrderLines()->exists())
                && (
                    (int) $attributes['item_id'] !== $price->item_id
                    || (int) ($attributes['supplier_id'] ?? 0) !== (int) ($price->supplier_id ?? 0)
                    || $attributes['price_type'] !== $price->price_type
                    || $this->normalizeDecimal((string) $attributes['price']) !== $this->normalizeDecimal((string) $price->getRawOriginal('price'))
                    || $attributes['start_date'] !== $price->start_date->toDateString()
                )) {
                throw ValidationException::withMessages([
                    'price' => 'Harga, supplier, jenis, barang, dan tanggal mulai tidak dapat diubah karena sudah dipakai transaksi. Ubah tanggal akhirnya atau tambahkan harga baru.',
                ]);
            }

            $this->ensureNoOverlap($attributes, $price->id);
            $price->update($attributes);

            return $price;
        });
    }

    public function delete(int $id, ?string $expectedType = null): void
    {
        $price = $this->find($id, $expectedType);

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

    /** @param array<string, mixed> $attributes */
    private function ensureNoOverlap(array $attributes, ?int $ignoreId = null): void
    {
        $overlap = MasterPrice::query()
            ->where('item_id', (int) $attributes['item_id'])
            ->where('price_type', $attributes['price_type'] ?? 'sell')
            ->when(
                ($attributes['price_type'] ?? 'sell') === 'buy',
                fn ($query) => $query->where('supplier_id', $attributes['supplier_id'] ?? null),
                fn ($query) => $query->whereNull('supplier_id'),
            )
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function normalizeSupplier(array &$attributes): void
    {
        if (($attributes['price_type'] ?? 'sell') === 'sell') {
            $attributes['supplier_id'] = null;

            return;
        }

        if (empty($attributes['supplier_id']) || ! Supplier::query()->whereKey($attributes['supplier_id'])->exists()) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier wajib dipilih untuk harga beli.',
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
