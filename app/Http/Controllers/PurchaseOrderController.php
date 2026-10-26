<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderRequest;
use App\Services\PurchasingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchasingService $purchasing) {}

    public function index(): View
    {
        return view('purchase-orders.index', [
            'orders' => $this->purchasing->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('purchase-orders.form', [
            'suppliers' => $this->purchasing->activeSuppliers(),
            'items' => $this->purchasing->activeItems(),
        ]);
    }

    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $order = $this->purchasing->create($request->validated());

        return Redirect::route('purchase-orders.show', $order)
            ->with('status', 'Purchase Order berhasil dibuat.');
    }

    public function show(int $purchaseOrder): View
    {
        return view('purchase-orders.show', [
            'order' => $this->purchasing->find($purchaseOrder),
        ]);
    }
}
