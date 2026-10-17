<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SalesOrderRequest;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class SalesOrderController extends Controller
{
    public function __construct(private readonly SalesOrderService $salesOrders) {}

    public function index(): View
    {
        return view('sales-orders.index', [
            'orders' => $this->salesOrders->paginate(),
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(SalesOrderRequest $request): RedirectResponse
    {
        $order = $this->salesOrders->create($request->validated());

        return Redirect::route('sales-orders.show', $order)->with('status', 'Sales Order draft berhasil dibuat.');
    }

    public function show(int $salesOrder): View
    {
        return view('sales-orders.show', [
            'order' => $this->salesOrders->find($salesOrder),
        ]);
    }

    public function edit(int $salesOrder): View
    {
        $order = $this->salesOrders->find($salesOrder);

        abort_unless($order->status === 'draft', 404);

        return $this->form($order);
    }

    public function update(SalesOrderRequest $request, int $salesOrder): RedirectResponse
    {
        $this->salesOrders->updateDraft($salesOrder, $request->validated());

        return Redirect::route('sales-orders.show', $salesOrder)->with('status', 'Sales Order berhasil diperbarui.');
    }

    public function confirm(int $salesOrder): RedirectResponse
    {
        $this->salesOrders->confirm($salesOrder);

        return Redirect::route('sales-orders.show', $salesOrder)->with('status', 'Sales Order berhasil dikonfirmasi.');
    }

    private function form(?SalesOrder $order): View
    {
        return view('sales-orders.form', [
            'order' => $order,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'items' => $this->salesOrders->availableItems(),
        ]);
    }
}
