<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MasterPriceRequest;
use App\Http\Requests\PurchaseOrderPriceRequest;
use App\Http\Requests\SalesOrderPriceRequest;
use App\Models\Item;
use App\Models\MasterPrice;
use App\Models\Supplier;
use App\Services\MasterPriceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class MasterPriceController extends Controller
{
    public function __construct(private readonly MasterPriceService $prices) {}

    public function index(string $priceType): View
    {
        return view('master-prices.index', [
            'prices' => $this->prices->paginate($priceType),
            'priceType' => $priceType,
        ]);
    }

    public function create(string $priceType): View
    {
        return $this->form(null, $priceType);
    }

    public function store(MasterPriceRequest $request, string $priceType): RedirectResponse
    {
        $this->prices->create($request->validated());

        return Redirect::route('master-prices.index', ['priceType' => $priceType])
            ->with('status', 'Harga baru berhasil ditambahkan. Riwayat harga sebelumnya tetap tersimpan.');
    }

    public function edit(string $priceType, int $masterPrice): View
    {
        return $this->form($this->prices->find($masterPrice, $priceType), $priceType);
    }

    public function update(MasterPriceRequest $request, string $priceType, int $masterPrice): RedirectResponse
    {
        $this->prices->update($masterPrice, $request->validated(), $priceType);

        return Redirect::route('master-prices.index', ['priceType' => $priceType])
            ->with('status', 'Periode harga berhasil diperbarui.');
    }

    public function destroy(string $priceType, int $masterPrice): RedirectResponse
    {
        $this->prices->delete($masterPrice, $priceType);

        return Redirect::route('master-prices.index', ['priceType' => $priceType])
            ->with('status', 'Riwayat harga berhasil dihapus.');
    }

    public function lookup(SalesOrderPriceRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        $price = $this->prices->priceForDate((int) $attributes['item_id'], $attributes['order_date']);

        if ($price === null) {
            return response()->json([
                'message' => 'Harga belum ditetapkan untuk barang ini pada tanggal Sales Order tersebut.',
            ], 404);
        }

        return response()->json([
            'price' => $price->price,
            'start_date' => $price->start_date->toDateString(),
            'end_date' => $price->end_date?->toDateString(),
        ]);
    }

    public function lookupPurchaseOrderPrice(PurchaseOrderPriceRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        $supplierId = (int) $attributes['supplier_id'];
        $price = $this->prices->priceForDate(
            (int) $attributes['item_id'],
            $attributes['order_date'],
            type: 'buy',
            supplierId: $supplierId,
        );
        $comparisons = $this->prices->buyPricesForDate((int) $attributes['item_id'], $attributes['order_date'])
            ->map(fn (MasterPrice $comparison): array => [
                'supplier' => $comparison->supplier->name,
                'price' => $comparison->price,
                'selected' => $comparison->supplier_id === $supplierId,
            ])
            ->all();

        if ($price === null) {
            return response()->json([
                'message' => 'Supplier ini belum memiliki harga beli untuk barang pada tanggal Purchase Order.',
                'comparisons' => $comparisons,
            ], 404);
        }

        return response()->json([
            'price' => $price->price,
            'start_date' => $price->start_date->toDateString(),
            'end_date' => $price->end_date?->toDateString(),
            'comparisons' => $comparisons,
        ]);
    }

    private function form(?MasterPrice $price, string $priceType): View
    {
        return view('master-prices.form', [
            'price' => $price,
            'priceType' => $priceType,
            'items' => Item::query()->orderBy('name')->get(),
            'suppliers' => $priceType === 'buy'
                ? Supplier::query()->where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
