<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GoodsReceiptRequest;
use App\Services\PurchasingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class ReceivingController extends Controller
{
    public function __construct(private readonly PurchasingService $purchasing) {}

    public function index(): View
    {
        return view('receivings.index', [
            'receipts' => $this->purchasing->paginateReceipts(),
        ]);
    }

    public function create(Request $request): View
    {
        $orders = $this->purchasing->receivableOrders();
        $purchaseOrderId = $request->integer('purchase_order_id') ?: null;
        $order = $purchaseOrderId === null
            ? null
            : $orders->firstWhere('id', $purchaseOrderId);

        return view('receivings.create', [
            'orders' => $orders,
            'order' => $order,
            'warehouses' => $this->purchasing->activeWarehouses(),
        ]);
    }

    public function store(GoodsReceiptRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $receipt = $this->purchasing->receive((int) $attributes['purchase_order_id'], $attributes);

        return Redirect::route('receivings.index')
            ->with('status', "Receiving untuk PO {$receipt->reference_number} berhasil dicatat dan stok diperbarui.");
    }
}
