<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeliveryService
{
    public function __construct(
        private readonly InventoryNumberGenerator $numberGenerator,
        private readonly StockLevelService $stockLevels,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Delivery>
     */
    public function paginate(): LengthAwarePaginator
    {
        return Delivery::query()
            ->with(['salesOrder.customer', 'warehouse'])
            ->withCount('lines')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Delivery>
     */
    public function uninvoiced(): Collection
    {
        return Delivery::query()
            ->with(['salesOrder.customer'])
            ->withCount('lines')
            ->where('status', 'delivered')
            ->whereDoesntHave('invoices')
            ->latest('id')
            ->get();
    }

    /**
     * @return Collection<int, SalesOrder>
     */
    public function availableOrders(): Collection
    {
        return SalesOrder::query()
            ->with(['customer', 'lines.item.unit'])
            ->where('status', 'confirmed')
            ->latest('id')
            ->get();
    }

    public function find(int $id): Delivery
    {
        return Delivery::query()
            ->with(['salesOrder.customer', 'warehouse', 'lines.item.unit', 'lines.location', 'lines.salesOrderLine.item', 'invoices'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Delivery
    {
        return DB::transaction(function () use ($attributes): Delivery {
            $salesOrder = SalesOrder::query()->lockForUpdate()->findOrFail($attributes['sales_order_id']);
            if ($salesOrder->status !== 'confirmed') {
                throw ValidationException::withMessages(['sales_order_id' => 'Delivery hanya dapat dibuat untuk Sales Order yang sudah dikonfirmasi.']);
            }

            $warehouse = Warehouse::query()
                ->whereKey($attributes['warehouse_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $delivery = Delivery::query()->create([
                'delivery_number' => $this->numberGenerator->next('DO', $attributes['delivery_date']),
                'sales_order_id' => $salesOrder->id,
                'warehouse_id' => $warehouse->id,
                'delivery_date' => $attributes['delivery_date'],
                'status' => 'delivered',
                'notes' => $attributes['notes'] ?? null,
            ]);

            $requested = [];
            foreach ($attributes['lines'] as $index => $lineAttributes) {
                $line = SalesOrderLine::query()
                    ->where('sales_order_id', $salesOrder->id)
                    ->lockForUpdate()
                    ->find($lineAttributes['sales_order_line_id']);

                if ($line === null) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.sales_order_line_id" => 'Barang harus berasal dari Sales Order terpilih.',
                    ]);
                }

                $requested[$line->id] = ($requested[$line->id] ?? 0) + (float) $lineAttributes['qty'];
                $shipped = (float) DeliveryLine::query()
                    ->where('sales_order_line_id', $line->id)
                    ->whereHas('delivery', fn ($query) => $query->where('status', 'delivered'))
                    ->sum('qty');

                if ($shipped + $requested[$line->id] > (float) $line->qty + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.qty" => 'Kuantitas delivery melebihi sisa Sales Order.',
                    ]);
                }

                $locationId = $lineAttributes['location_id'] ?? null;
                if ($locationId !== null && ! Location::query()
                    ->whereKey($locationId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.location_id" => 'Pilih lokasi aktif di gudang delivery.',
                    ]);
                }

                $qty = (string) $lineAttributes['qty'];
                $this->stockLevels->decrease(
                    (int) $line->item_id,
                    (int) $warehouse->id,
                    $locationId,
                    $qty,
                    "lines.{$index}.qty",
                );

                $deliveryLine = $delivery->lines()->create([
                    'sales_order_line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'location_id' => $locationId,
                    'qty' => $qty,
                ]);

                InventoryTransaction::query()->create([
                    'transaction_date' => $attributes['delivery_date'].' 00:00:00',
                    'item_id' => $line->item_id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'transaction_type' => 'out',
                    'qty' => $qty,
                    'unit_cost' => '0',
                    'reference_type' => DeliveryLine::class,
                    'reference_id' => $deliveryLine->id,
                    'notes' => "Pengiriman {$delivery->delivery_number}",
                ]);
            }

            return $delivery;
        });
    }
}
