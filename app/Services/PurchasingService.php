<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchasingService
{
    public function __construct(
        private readonly InventoryNumberGenerator $numberGenerator,
        private readonly MasterPriceService $prices,
        private readonly AccountingService $accounting,
    ) {}

    /**
     * @return LengthAwarePaginator<int, PurchaseOrder>
     */
    public function paginate(): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->withCount('goodsReceipts')
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function activeSuppliers(): Collection
    {
        return Supplier::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Item>
     */
    public function activeItems(): Collection
    {
        return Item::query()->with(['unit', 'supplierPrices'])->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Warehouse>
     */
    public function activeWarehouses(): Collection
    {
        return Warehouse::query()
            ->with(['locations' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, GoodsReceipt>
     */
    public function paginateReceipts(): LengthAwarePaginator
    {
        return GoodsReceipt::query()
            ->with(['purchaseOrder.supplier', 'warehouse'])
            ->withCount('lines')
            ->latest('receipt_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function receivableOrders(): Collection
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'lines.item.unit', 'lines.unit'])
            ->whereIn('status', ['open', 'ordered', 'partial', 'draft'])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (PurchaseOrder $order): bool => $order->lines->contains(
                fn (PurchaseOrderLine $line): bool => (float) $line->qty > (float) $line->qty_received,
            ))
            ->values();
    }

    public function find(int $id): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with([
                'supplier',
                'lines.item.unit',
                'lines.unit',
                'lines.masterPrice',
                'goodsReceipts.warehouse',
                'goodsReceipts.lines.item.unit',
                'goodsReceipts.lines.location',
            ])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes): PurchaseOrder {
            $supplier = Supplier::query()
                ->whereKey($attributes['supplier_id'])
                ->where('is_active', true)
                ->first();

            if ($supplier === null) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier tidak aktif atau tidak tersedia.',
                ]);
            }

            $items = Item::query()
                ->whereIn('id', collect($attributes['lines'])->pluck('item_id')->unique())
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($items->count() !== collect($attributes['lines'])->pluck('item_id')->unique()->count()) {
                throw ValidationException::withMessages([
                    'lines' => 'Salah satu barang sudah tidak aktif atau tidak tersedia.',
                ]);
            }

            $order = PurchaseOrder::query()->create([
                'po_number' => $this->numberGenerator->next('PO', $attributes['order_date']),
                'supplier_id' => $supplier->id,
                'order_date' => $attributes['order_date'],
                'expected_date' => $attributes['expected_date'] ?? null,
                'status' => 'open',
                'total_amount' => '0.00',
                'notes' => $attributes['notes'] ?? null,
            ]);

            $total = 0.0;
            foreach ($attributes['lines'] as $lineIndex => $line) {
                $item = $items->get((int) $line['item_id']);
                $masterPrice = $this->prices->priceForDate(
                    (int) $item->id,
                    $attributes['order_date'],
                    lock: true,
                    type: 'buy',
                    supplierId: $supplier->id,
                );

                if ($masterPrice === null) {
                    throw ValidationException::withMessages([
                        "lines.{$lineIndex}.item_id" => "Supplier {$supplier->name} belum memiliki harga beli {$item->code} yang berlaku pada tanggal Purchase Order.",
                    ]);
                }

                $lineTotal = round((float) $line['qty'] * (float) $masterPrice->price, 2);
                $total += $lineTotal;

                $order->lines()->create([
                    'item_id' => $item->id,
                    'qty' => $line['qty'],
                    'qty_received' => '0',
                    'unit_price' => $masterPrice->price,
                    'unit_id' => $item->unit_id,
                    'master_price_id' => $masterPrice->id,
                ]);
            }

            $order->update(['total_amount' => number_format($total, 2, '.', '')]);

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function receive(int $purchaseOrderId, array $attributes): GoodsReceipt
    {
        return DB::transaction(function () use ($purchaseOrderId, $attributes): GoodsReceipt {
            $attributes['lines'] = array_values(array_filter(
                $attributes['lines'],
                fn (array $line): bool => (float) $line['qty'] > 0,
            ));

            if ($attributes['lines'] === []) {
                throw ValidationException::withMessages([
                    'lines' => 'Isi kuantitas penerimaan minimal satu baris dengan nilai lebih dari nol.',
                ]);
            }

            $order = PurchaseOrder::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($purchaseOrderId);

            if (! in_array($order->status, ['open', 'ordered', 'partial', 'draft'], true)) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'PO ini sudah selesai atau tidak dapat menerima barang lagi.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->whereKey($attributes['warehouse_id'])
                ->where('is_active', true)
                ->first();

            if ($warehouse === null) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Gudang tidak aktif atau tidak tersedia.',
                ]);
            }

            $orderLines = $order->lines->keyBy('id');
            $receiptLines = [];
            $journalLines = [];

            foreach ($attributes['lines'] as $line) {
                $orderLine = $orderLines->get((int) $line['purchase_order_line_id']);

                if ($orderLine === null) {
                    throw ValidationException::withMessages([
                        'lines' => 'Baris penerimaan tidak sesuai dengan PO yang dipilih.',
                    ]);
                }

                $orderLine = PurchaseOrderLine::query()->lockForUpdate()->findOrFail($orderLine->id);
                $remaining = (float) $orderLine->qty - (float) $orderLine->qty_received;
                if ((float) $line['qty'] > $remaining + 0.0000001) {
                    throw ValidationException::withMessages([
                        'lines.'.array_search($line, $attributes['lines'], true).'.qty' => "Kuantitas penerimaan {$orderLine->item->code} melebihi sisa PO (".number_format(max(0, $remaining), 2, ',', '.').').',
                    ]);
                }

                $locationId = isset($line['location_id']) && $line['location_id'] !== ''
                    ? (int) $line['location_id']
                    : null;
                if ($locationId !== null && ! Location::query()
                    ->whereKey($locationId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'lines' => 'Lokasi harus aktif dan berada di gudang penerimaan.',
                    ]);
                }

                $receiptLines[] = [$orderLine, $line, $locationId];
            }

            $receipt = GoodsReceipt::query()->create([
                'gr_number' => $this->numberGenerator->next('GR', $attributes['receipt_date']),
                'reference_number' => $order->po_number,
                'purchase_order_id' => $order->id,
                'warehouse_id' => $warehouse->id,
                'receipt_date' => $attributes['receipt_date'],
                'status' => 'posted',
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($receiptLines as [$orderLine, $line, $locationId]) {
                $qty = (string) $line['qty'];
                $item = $orderLine->item;
                $unitCost = (string) $orderLine->unit_price;

                $receipt->lines()->create([
                    'purchase_order_line_id' => $orderLine->id,
                    'item_id' => $item->id,
                    'location_id' => $locationId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                $orderLine->update([
                    'qty_received' => number_format((float) $orderLine->qty_received + (float) $qty, 4, '.', ''),
                ]);

                $this->increaseStock($item->id, $warehouse->id, $locationId, $qty);

                InventoryTransaction::query()->create([
                    'transaction_date' => $attributes['receipt_date'].' 00:00:00',
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $locationId,
                    'transaction_type' => 'in',
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $receipt->id,
                    'notes' => $attributes['notes'] ?? null,
                ]);

                $amountCents = AccountingService::valueCents($qty, $unitCost);
                $journalLines[] = [
                    'account_code' => $this->accounting->inventoryAccountCode($item->item_type),
                    'debit_cents' => $amountCents,
                    'credit_cents' => 0,
                    'description' => "Persediaan {$item->name}",
                ];
                $journalLines[] = [
                    'account_code' => '2190',
                    'debit_cents' => 0,
                    'credit_cents' => $amountCents,
                    'description' => "GRNI {$item->name}",
                ];
            }

            $remainingLines = PurchaseOrderLine::query()
                ->where('purchase_order_id', $order->id)
                ->whereColumn('qty_received', '<', 'qty')
                ->exists();

            $order->update(['status' => $remainingLines ? 'partial' : 'received']);

            $this->accounting->post($receipt, $receipt->receipt_date->toDateString(), $receipt->gr_number, "Penerimaan pembelian {$receipt->gr_number}", $journalLines);

            return $receipt;
        });
    }

    private function increaseStock(int $itemId, int $warehouseId, ?int $locationId, string $qty): void
    {
        $query = StockLevel::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId);
        $locationId === null
            ? $query->whereNull('location_id')
            : $query->where('location_id', $locationId);

        $level = $query->lockForUpdate()->first();
        if ($level === null) {
            StockLevel::query()->create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'qty_on_hand' => $qty,
                'qty_reserved' => '0',
                'qty_available' => $qty,
            ]);

            return;
        }

        $onHand = (float) $level->qty_on_hand + (float) $qty;
        $level->update([
            'qty_on_hand' => number_format($onHand, 4, '.', ''),
            'qty_available' => number_format($onHand - (float) $level->qty_reserved, 4, '.', ''),
        ]);
    }
}
