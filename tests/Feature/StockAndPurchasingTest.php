<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\MasterPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StockAndPurchasingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_adjustment_in_adds_stock_and_an_inventory_transaction(): void
    {
        $item = Item::factory()->create();
        $warehouse = $this->createWarehouse();
        $location = Location::query()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'A-01',
            'name' => 'Rak A-01',
            'is_active' => true,
        ]);

        $this->post(route('stock.adjustments.store', ['direction' => 'in']), [
            'adjustment_date' => '2026-10-21',
            'notes' => 'Penambahan stok gudang',
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'location_id' => $location->id,
                'qty' => '12.5000',
                'unit_cost' => '2500.0000',
            ]],
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('stock_adjustments', [
            'adjustment_number' => 'AI261000001',
            'direction' => 'in',
        ]);
        $this->assertDatabaseHas('stock_adjustment_lines', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '12.5000',
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'location_id' => $location->id,
            'qty_on_hand' => '12.5000',
            'qty_available' => '12.5000',
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'in',
            'reference_type' => StockAdjustment::class,
            'qty' => '12.5000',
        ]);
        $this->assertInstanceOf(StockAdjustment::class, InventoryTransaction::query()->firstOrFail()->reference);
    }

    public function test_stock_and_purchase_order_pages_render(): void
    {
        $supplier = $this->createSupplier();
        $item = Item::factory()->create();

        $this->get(route('stock.index'))->assertOk()->assertSee('Saldo persediaan')->assertSee('Adjustment In')->assertSee('Adjustment Out')->assertSee('PIC umum (acuan belajar)');
        $this->get(route('stock.adjustments.create', ['direction' => 'in']))->assertOk()->assertSee('Adjustment In Stock');
        $this->get(route('stock.adjustments.create', ['direction' => 'out']))->assertOk()->assertSee('Adjustment Out Stock');
        $this->get(route('purchase-orders.index'))->assertOk()->assertSee('Daftar Purchase Order')->assertSee('PIC umum (acuan belajar)');
        $this->get(route('purchase-orders.create'))->assertOk()->assertSee('Buat Purchase Order');

        $order = PurchaseOrder::query()->create([
            'po_number' => 'PO261009998',
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-21',
            'status' => 'open',
            'total_amount' => '100.00',
        ]);
        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => '1.0000',
            'qty_received' => '0',
            'unit_price' => '100.0000',
            'unit_id' => $item->unit_id,
        ]);

        $this->get(route('purchase-orders.show', $order))->assertOk()->assertSee($order->po_number)->assertSee('PIC umum (acuan belajar)');
    }

    public function test_stock_adjustment_out_decreases_available_stock(): void
    {
        $item = Item::factory()->create();
        $warehouse = $this->createWarehouse();
        StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '8.0000',
            'qty_reserved' => '2.0000',
            'qty_available' => '6.0000',
        ]);

        $this->post(route('stock.adjustments.store', ['direction' => 'out']), [
            'adjustment_date' => '2026-10-21',
            'notes' => 'Barang rusak',
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'location_id' => '',
                'qty' => '4.0000',
            ]],
        ])->assertRedirect(route('stock.index'));

        $level = StockLevel::query()->firstOrFail();
        $this->assertSame('4.0000', $level->qty_on_hand);
        $this->assertSame('2.0000', $level->qty_reserved);
        $this->assertSame('2.0000', $level->qty_available);
        $this->assertDatabaseHas('stock_adjustments', [
            'adjustment_number' => 'AO261000001',
            'direction' => 'out',
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'out',
            'qty' => '4.0000',
            'reference_type' => StockAdjustment::class,
        ]);
    }

    public function test_stock_adjustment_out_cannot_exceed_available_quantity(): void
    {
        $item = Item::factory()->create();
        $warehouse = $this->createWarehouse();
        StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '8.0000',
            'qty_reserved' => '2.0000',
            'qty_available' => '6.0000',
        ]);

        $this->from(route('stock.adjustments.create', ['direction' => 'out']))
            ->post(route('stock.adjustments.store', ['direction' => 'out']), [
                'adjustment_date' => '2026-10-21',
                'lines' => [[
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => '',
                    'qty' => '6.0001',
                ]],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertSame('8.0000', StockLevel::query()->firstOrFail()->qty_on_hand);
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_purchase_order_receipts_support_partial_receiving_and_update_stock(): void
    {
        $supplier = $this->createSupplier();
        $item = Item::factory()->create();
        $warehouse = $this->createWarehouse();
        $masterPrice = app(MasterPriceService::class)->create([
            'item_id' => $item->id,
            'price_type' => 'buy',
            'price' => '1250.0000',
            'start_date' => '2026-10-01',
            'end_date' => null,
        ]);

        $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-21',
            'expected_date' => '2026-10-30',
            'lines' => [['item_id' => $item->id, 'qty' => '5.0000']],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->with('lines')->firstOrFail();
        $this->assertSame('PO261000001', $order->po_number);
        $this->assertSame('6250.00', $order->total_amount);
        $this->assertSame($masterPrice->id, $order->lines->first()->master_price_id);
        $this->assertSame('1250.0000', $order->lines->first()->unit_price);
        $this->from(route('master-prices.edit', $masterPrice))
            ->put(route('master-prices.update', $masterPrice), [
                'item_id' => $item->id,
                'price_type' => 'buy',
                'price' => '1500.0000',
                'start_date' => '2026-10-01',
                'end_date' => null,
            ])
            ->assertSessionHasErrors('price');
        $this->assertSame('1250.0000', $masterPrice->fresh()->price);

        $this->post(route('purchase-orders.receipts.store', $order), [
            'warehouse_id' => $warehouse->id,
            'receipt_date' => '2026-10-22',
            'lines' => [[
                'purchase_order_line_id' => $order->lines->first()->id,
                'qty' => '3.0000',
                'location_id' => '',
            ]],
        ])->assertRedirect(route('purchase-orders.show', $order));

        $order->refresh();
        $line = $order->lines()->firstOrFail();
        $this->assertSame('partial', $order->status);
        $this->assertSame('3.0000', $line->qty_received);
        $this->assertDatabaseHas('stock_levels', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '3.0000',
            'qty_available' => '3.0000',
        ]);

        $this->post(route('purchase-orders.receipts.store', $order), [
            'warehouse_id' => $warehouse->id,
            'receipt_date' => '2026-10-23',
            'lines' => [[
                'purchase_order_line_id' => $line->id,
                'qty' => '2.0000',
                'location_id' => '',
            ]],
        ])->assertRedirect(route('purchase-orders.show', $order));

        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame('5.0000', $line->fresh()->qty_received);
        $this->assertSame('5.0000', StockLevel::query()->firstOrFail()->qty_on_hand);
        $this->assertSame(2, GoodsReceipt::query()->count());
        $this->assertSame(2, InventoryTransaction::query()->count());
        $this->assertSame(1, GoodsReceipt::query()->firstOrFail()->inventoryTransactions()->count());
    }

    public function test_purchase_order_requires_a_valid_buy_price_even_when_sale_price_exists(): void
    {
        $supplier = $this->createSupplier();
        $item = Item::factory()->create();
        app(MasterPriceService::class)->create([
            'item_id' => $item->id,
            'price_type' => 'sell',
            'price' => '1500.0000',
            'start_date' => '2026-10-01',
            'end_date' => null,
        ]);

        $this->from(route('purchase-orders.create'))
            ->post(route('purchase-orders.store'), [
                'supplier_id' => $supplier->id,
                'order_date' => '2026-10-21',
                'lines' => [['item_id' => $item->id, 'qty' => '1.0000']],
            ])
            ->assertSessionHasErrors('lines.0.item_id');

        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('purchase_order_lines', 0);
    }

    public function test_receipt_cannot_exceed_the_remaining_purchase_order_quantity(): void
    {
        $order = $this->createPurchaseOrder();
        $warehouse = $this->createWarehouse();
        $line = $order->lines()->firstOrFail();

        $this->from(route('purchase-orders.show', $order))
            ->post(route('purchase-orders.receipts.store', $order), [
                'warehouse_id' => $warehouse->id,
                'receipt_date' => '2026-10-21',
                'lines' => [[
                    'purchase_order_line_id' => $line->id,
                    'qty' => '5.0000',
                    'location_id' => '',
                ]],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_receipt_rejects_location_from_a_different_warehouse(): void
    {
        $order = $this->createPurchaseOrder();
        $warehouse = $this->createWarehouse();
        $otherWarehouse = $this->createWarehouse('WH-OTHER');
        $location = Location::query()->create([
            'warehouse_id' => $otherWarehouse->id,
            'code' => 'B-01',
            'name' => 'Rak B-01',
            'is_active' => true,
        ]);

        $this->from(route('purchase-orders.show', $order))
            ->post(route('purchase-orders.receipts.store', $order), [
                'warehouse_id' => $warehouse->id,
                'receipt_date' => '2026-10-21',
                'lines' => [[
                    'purchase_order_line_id' => $order->lines()->firstOrFail()->id,
                    'qty' => '1.0000',
                    'location_id' => $location->id,
                ]],
            ])
            ->assertSessionHasErrors('lines.0.location_id');

        $this->assertDatabaseCount('goods_receipts', 0);
    }

    private function createPurchaseOrder(): PurchaseOrder
    {
        $supplier = $this->createSupplier();
        $item = Item::factory()->create();
        $order = PurchaseOrder::query()->create([
            'po_number' => 'PO261009999',
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-21',
            'status' => 'open',
            'total_amount' => '5000.00',
        ]);
        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => '4.0000',
            'qty_received' => '0',
            'unit_price' => '1250.0000',
            'unit_id' => $item->unit_id,
        ]);

        return $order;
    }

    private function createWarehouse(string $code = 'WH-TEST'): Warehouse
    {
        return Warehouse::query()->create([
            'code' => $code,
            'name' => 'Gudang '.$code,
            'is_active' => true,
        ]);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::query()->create([
            'code' => 'SUP-TEST',
            'name' => 'Supplier Test',
            'is_active' => true,
        ]);
    }
}
