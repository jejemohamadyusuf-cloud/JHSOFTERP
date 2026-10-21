<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        return view('stock.index', [
            'stockLevels' => $this->stock->paginate($request->string('search')->toString()),
            'adjustments' => $this->stock->recentAdjustments(),
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function createAdjustment(string $direction): View
    {
        abort_unless(in_array($direction, ['in', 'out'], true), 404);

        return view('stock.adjustment-form', [
            'direction' => $direction,
            'items' => $this->stock->activeItems(),
            'warehouses' => $this->stock->activeWarehouses(),
        ]);
    }

    public function storeAdjustment(StockAdjustmentRequest $request, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['in', 'out'], true), 404);

        $adjustment = $this->stock->createAdjustment($direction, $request->validated());

        return Redirect::route('stock.index')
            ->with('status', "Adjustment {$adjustment->adjustment_number} berhasil dicatat.");
    }
}
