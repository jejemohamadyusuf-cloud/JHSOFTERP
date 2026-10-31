<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnRequest;
use App\Services\ReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class ReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returns) {}

    public function salesIndex(): View
    {
        return view('returns.sales-index', ['returns' => $this->returns->paginateSales()]);
    }

    public function salesCreate(Request $request): View
    {
        $sources = $this->returns->salesSources();
        $source = $sources->firstWhere('id', $request->integer('source_id'));

        return view('returns.sales-create', compact('sources', 'source'));
    }

    public function storeSales(StoreReturnRequest $request): RedirectResponse
    {
        $return = $this->returns->createSales($request->validated());

        return Redirect::route('sales-returns.index')
            ->with('status', "Retur penjualan {$return->return_number} berhasil dicatat dan stok diperbarui.");
    }

    public function purchaseIndex(): View
    {
        return view('returns.purchase-index', ['returns' => $this->returns->paginatePurchases()]);
    }

    public function purchaseCreate(Request $request): View
    {
        $sources = $this->returns->purchaseSources();
        $source = $sources->firstWhere('id', $request->integer('source_id'));

        return view('returns.purchase-create', compact('sources', 'source'));
    }

    public function storePurchase(StoreReturnRequest $request): RedirectResponse
    {
        $return = $this->returns->createPurchase($request->validated());

        return Redirect::route('purchase-returns.index')
            ->with('status', "Retur pembelian {$return->return_number} berhasil dicatat dan stok diperbarui.");
    }
}
