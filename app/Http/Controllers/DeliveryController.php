<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryRequest;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\DeliveryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function index(): View
    {
        return view('deliveries.index', ['deliveries' => $this->deliveries->paginate()]);
    }

    public function create(Request $request): View
    {
        $salesOrder = null;
        if ($request->filled('sales_order_id')) {
            $salesOrder = SalesOrder::query()
                ->with(['customer', 'lines.item.unit', 'lines.deliveryLines.delivery'])
                ->where('status', 'confirmed')
                ->findOrFail($request->integer('sales_order_id'));
        }

        $hasRemainingLines = $salesOrder?->lines->contains(function ($line): bool {
            $delivered = $line->deliveryLines
                ->filter(fn ($deliveryLine) => $deliveryLine->delivery?->status === 'delivered')
                ->sum('qty');

            return (float) $line->qty - (float) $delivered > 0.0000001;
        }) ?? false;

        return view('deliveries.create', [
            'salesOrders' => $this->deliveries->availableOrders(),
            'salesOrder' => $salesOrder,
            'hasRemainingLines' => $hasRemainingLines,
            'warehouses' => Warehouse::query()
                ->with(['locations' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(DeliveryRequest $request): RedirectResponse
    {
        $delivery = $this->deliveries->create($request->validated());

        return Redirect::route('deliveries.show', $delivery)->with('status', 'Delivery berhasil diposting dan stok telah dikurangi.');
    }

    public function show(int $delivery): View
    {
        return view('deliveries.show', ['delivery' => $this->deliveries->find($delivery)]);
    }
}
