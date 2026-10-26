<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkOrderRequest;
use App\Models\Location;
use App\Services\WorkOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class WorkOrderController extends Controller
{
    public function __construct(private readonly WorkOrderService $workOrders) {}

    public function index(): View
    {
        return view('work-orders.index', [
            'workOrders' => $this->workOrders->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $salesOrderId = $request->integer('sales_order_id') ?: null;

        return view('work-orders.create', [
            'salesOrderLines' => $this->workOrders->availableSalesOrderLines($salesOrderId),
            'makeToStockItems' => $this->workOrders->makeToStockItems(),
            'boms' => $this->workOrders->activeBoms(),
            'warehouses' => $this->workOrders->activeWarehouses(),
        ]);
    }

    public function store(WorkOrderRequest $request): RedirectResponse
    {
        $workOrder = $this->workOrders->create($request->validated());

        return Redirect::route('work-orders.show', $workOrder)
            ->with('status', 'Work Order rencana berhasil dibuat. Stok belum berubah.');
    }

    public function show(int $workOrder): View
    {
        $workOrder = $this->workOrders->find($workOrder);

        return view('work-orders.show', [
            'workOrder' => $workOrder,
            'locations' => Location::query()
                ->where('warehouse_id', $workOrder->warehouse_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
