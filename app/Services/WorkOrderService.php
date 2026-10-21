<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bom;
use App\Models\Item;
use App\Models\SalesOrderLine;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WorkOrderService
{
    public function __construct(private readonly WorkOrderNumberGenerator $numberGenerator) {}

    /**
     * @return LengthAwarePaginator<int, WorkOrder>
     */
    public function paginate(): LengthAwarePaginator
    {
        return WorkOrder::query()
            ->with(['item', 'salesOrder', 'warehouse', 'bom'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, SalesOrderLine>
     */
    public function availableSalesOrderLines(?int $salesOrderId = null): Collection
    {
        return SalesOrderLine::query()
            ->whereHas('salesOrder', fn ($query) => $query->where('status', 'confirmed'))
            ->when($salesOrderId !== null, fn ($query) => $query->where('sales_order_id', $salesOrderId))
            ->with(['salesOrder.customer', 'item.unit'])
            ->withSum([
                'workOrders as qty_work_ordered' => fn ($query) => $query->where('status', '!=', 'cancelled'),
            ], 'qty_planned')
            ->orderBy('sales_order_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (SalesOrderLine $line): bool => (float) $line->qty - (float) ($line->qty_work_ordered ?? 0) > 0)
            ->values();
    }

    /**
     * @return Collection<int, Bom>
     */
    public function activeBoms(): Collection
    {
        return Bom::query()
            ->with('item')
            ->where('is_active', true)
            ->orderBy('item_id')
            ->orderBy('version')
            ->get();
    }

    /**
     * @return Collection<int, Item>
     */
    public function makeToStockItems(): Collection
    {
        return Item::query()
            ->with('unit')
            ->where('is_active', true)
            ->where('item_type', 'finished')
            ->whereHas('boms', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Warehouse>
     */
    public function activeWarehouses(): Collection
    {
        return Warehouse::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function find(int $id): WorkOrder
    {
        return WorkOrder::query()
            ->with([
                'item.unit',
                'salesOrder.customer',
                'salesOrderLine',
                'warehouse',
                'bom.item',
                'bom.lines.component.unit',
                'bom.lines.unit',
            ])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): WorkOrder
    {
        return DB::transaction(function () use ($attributes): WorkOrder {
            $productionType = $attributes['production_type'] ?? 'make_to_order';
            $salesOrderLine = null;

            if ($productionType === 'make_to_order') {
                $salesOrderLine = SalesOrderLine::query()
                    ->with('salesOrder')
                    ->lockForUpdate()
                    ->findOrFail($attributes['sales_order_line_id']);

                if ($salesOrderLine->salesOrder->status !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'sales_order_line_id' => 'Work Order make to order hanya dapat dibuat dari Sales Order yang sudah dikonfirmasi.',
                    ]);
                }

                $itemId = (int) $salesOrderLine->item_id;
            } else {
                $item = Item::query()
                    ->whereKey($attributes['item_id'])
                    ->where('is_active', true)
                    ->where('item_type', 'finished')
                    ->first();

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'item_id' => 'Make to stock hanya dapat dibuat untuk barang jadi yang aktif.',
                    ]);
                }

                $itemId = (int) $item->id;
            }

            $bom = Bom::query()
                ->whereKey($attributes['bom_id'])
                ->where('item_id', $itemId)
                ->where('is_active', true)
                ->first();

            if ($bom === null) {
                throw ValidationException::withMessages([
                    'bom_id' => 'Pilih BOM aktif yang sesuai dengan barang Work Order.',
                ]);
            }

            if ($salesOrderLine !== null) {
                $qtyAlreadyPlanned = (float) WorkOrder::query()
                    ->where('sales_order_line_id', $salesOrderLine->id)
                    ->where('status', '!=', 'cancelled')
                    ->sum('qty_planned');
                $qtyRemaining = (float) $salesOrderLine->qty - $qtyAlreadyPlanned;

                if ((float) $attributes['qty_planned'] > $qtyRemaining) {
                    throw ValidationException::withMessages([
                        'qty_planned' => 'Kuantitas Work Order melebihi sisa kuantitas pada Sales Order.',
                    ]);
                }
            }

            $orderDate = now()->toDateString();

            return WorkOrder::query()->create([
                'wo_number' => $this->numberGenerator->next($orderDate),
                'production_type' => $productionType,
                'sales_order_id' => $salesOrderLine?->sales_order_id,
                'sales_order_line_id' => $salesOrderLine?->id,
                'item_id' => $itemId,
                'bom_id' => $bom->id,
                'warehouse_id' => $attributes['warehouse_id'],
                'qty_planned' => $attributes['qty_planned'],
                'qty_produced' => '0',
                'status' => 'planned',
                'start_date' => $attributes['start_date'] ?? null,
                'end_date' => $attributes['end_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ]);
        });
    }
}
