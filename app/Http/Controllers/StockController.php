<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockTakeRequest;
use App\Models\Location;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        return view('stock.index', [
            'stockLevels' => $this->stock->paginate($request->string('search')->toString()),
            'adjustments' => $this->stock->recentAdjustments(),
            'stockTakes' => $this->stock->recentStockTakes(),
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

    public function createStockTake(): View
    {
        return view('stock.take-form', [
            'items' => $this->stock->activeItems(),
            'warehouses' => $this->stock->activeWarehouses(),
        ]);
    }

    public function stockTakePosition(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
        ]);
        $locationId = empty($data['location_id']) ? null : (int) $data['location_id'];

        if ($locationId !== null && ! Location::query()
            ->whereKey($locationId)
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('is_active', true)
            ->exists()) {
            throw ValidationException::withMessages([
                'location_id' => 'Lokasi harus aktif dan berada di gudang yang dipilih.',
            ]);
        }

        return response()->json($this->stock->stockTakePosition(
            (int) $data['item_id'],
            (int) $data['warehouse_id'],
            $locationId,
        ));
    }

    public function storeStockTake(StockTakeRequest $request): RedirectResponse
    {
        $stockTake = $this->stock->createStockTake($request->validated());

        return Redirect::route('stock.index')
            ->with('status', "Stock take {$stockTake->stock_take_number} berhasil dicatat dan stok telah direkonsiliasi.");
    }
}
