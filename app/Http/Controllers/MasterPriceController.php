<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MasterPriceRequest;
use App\Http\Requests\SalesOrderPriceRequest;
use App\Models\Item;
use App\Models\MasterPrice;
use App\Services\MasterPriceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class MasterPriceController extends Controller
{
    public function __construct(private readonly MasterPriceService $prices) {}

    public function index(): View
    {
        return view('master-prices.index', ['prices' => $this->prices->paginate()]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(MasterPriceRequest $request): RedirectResponse
    {
        $this->prices->create($request->validated());

        return Redirect::route('master-prices.index')
            ->with('status', 'Harga baru berhasil ditambahkan. Riwayat harga sebelumnya tetap tersimpan.');
    }

    public function edit(int $masterPrice): View
    {
        return $this->form($this->prices->find($masterPrice));
    }

    public function update(MasterPriceRequest $request, int $masterPrice): RedirectResponse
    {
        $this->prices->update($masterPrice, $request->validated());

        return Redirect::route('master-prices.index')->with('status', 'Periode harga berhasil diperbarui.');
    }

    public function destroy(int $masterPrice): RedirectResponse
    {
        $this->prices->delete($masterPrice);

        return Redirect::route('master-prices.index')->with('status', 'Riwayat harga berhasil dihapus.');
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

    private function form(?MasterPrice $price): View
    {
        return view('master-prices.form', [
            'price' => $price,
            'items' => Item::query()->orderBy('name')->get(),
        ]);
    }
}
