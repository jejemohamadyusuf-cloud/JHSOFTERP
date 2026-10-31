<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturnLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesReturnLine;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ReturnsTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_sales_return_restock_is_limited_to_the_delivered_quantity(): void
    {
        [$delivery, $deliveryLine, $item, $warehouse, $stock] = $this->createSalesDelivery();

        $this->get(route('sales-returns.create', ['source_id' => $delivery->id]))
            ->assertOk()
            ->assertSee($delivery->delivery_number)
            ->assertSee('Buat Retur Penjualan');

        $this->post(route('sales-returns.store'), [
            'source_id' => $delivery->id,
            'return_date' => '2026-10-25',
            'reason' => 'Barang rusak',
            'lines' => [['source_line_id' => $deliveryLine->id, 'qty' => '2.00']],
        ])->assertRedirect(route('sales-returns.index'));

        $this->assertSame('4.0000', $stock->fresh()->qty_on_hand);
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'in',
            'reference_type' => SalesReturnLine::class,
            'qty' => '2.0000',
            'unit_cost' => '0.0000',
        ]);
        $this->get(route('sales-returns.index'))
            ->assertOk()
            ->assertSee('Barang rusak')
            ->assertSee('SR261000001');

        $this->from(route('sales-returns.create', ['source_id' => $delivery->id]))
            ->post(route('sales-returns.store'), [
                'source_id' => $delivery->id,
                'return_date' => '2026-10-26',
                'reason' => 'Kelebihan retur',
                'lines' => [['source_line_id' => $deliveryLine->id, 'qty' => '4.00']],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('sales_returns', 1);
        $this->assertSame('4.0000', $stock->fresh()->qty_on_hand);
        $this->assertSame(2, InventoryTransaction::query()->count());
    }

    public function test_purchase_return_removes_stock_and_cannot_exceed_receipt_quantity(): void
    {
        [$receipt, $receiptLine, $stock] = $this->createPurchaseReceipt();

        $this->get(route('purchase-returns.create', ['source_id' => $receipt->id]))
            ->assertOk()
            ->assertSee($receipt->gr_number)
            ->assertSee('Buat Retur Pembelian');

        $this->post(route('purchase-returns.store'), [
            'source_id' => $receipt->id,
            'return_date' => '2026-10-25',
            'reason' => 'Barang salah',
            'lines' => [['source_line_id' => $receiptLine->id, 'qty' => '1.50']],
        ])->assertRedirect(route('purchase-returns.index'));

        $this->assertSame('3.5000', $stock->fresh()->qty_on_hand);
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'out',
            'reference_type' => PurchaseReturnLine::class,
            'qty' => '1.5000',
            'unit_cost' => '125.0000',
        ]);
        $this->get(route('purchase-returns.index'))
            ->assertOk()
            ->assertSee('Barang salah')
            ->assertSee('PR261000001');

        $this->from(route('purchase-returns.create', ['source_id' => $receipt->id]))
            ->post(route('purchase-returns.store'), [
                'source_id' => $receipt->id,
                'return_date' => '2026-10-26',
                'reason' => 'Kelebihan retur',
                'lines' => [['source_line_id' => $receiptLine->id, 'qty' => '3.60']],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('purchase_returns', 1);
        $this->assertSame('3.5000', $stock->fresh()->qty_on_hand);
        $this->assertSame(2, InventoryTransaction::query()->count());
    }

    public function test_return_menus_follow_separate_sales_and_inventory_access(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $inventory = User::factory()->create(['role' => 'inventory']);

        $this->actingAs($sales)
            ->get(route('sales-returns.index'))
            ->assertOk()
            ->assertSee('Retur Penjualan');
        $this->get(route('purchase-returns.index'))->assertForbidden();

        $this->actingAs($inventory)
            ->get(route('purchase-returns.index'))
            ->assertOk()
            ->assertSee('Retur Pembelian');
        $this->get(route('sales-returns.index'))->assertForbidden();
    }

    private function createSalesDelivery(): array
    {
        $item = Item::factory()->create(['item_type' => 'finished']);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-RET-S',
            'name' => 'Gudang Retur Penjualan',
            'is_active' => true,
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '5.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '5.0000',
        ]);
        $order = SalesOrder::query()->create([
            'so_number' => 'SO-RET-001',
            'customer_id' => Customer::factory()->create()->id,
            'order_date' => '2026-10-20',
            'status' => 'confirmed',
            'total_amount' => '500.00',
        ]);
        $orderLine = SalesOrderLine::query()->create([
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => '5.0000',
            'unit_price' => '100.0000',
            'unit_id' => $item->unit_id,
        ]);

        $this->post(route('deliveries.store'), [
            'sales_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => '2026-10-21',
            'lines' => [['sales_order_line_id' => $orderLine->id, 'qty' => '3.00']],
        ])->assertRedirect();

        $delivery = Delivery::query()->firstOrFail();

        return [$delivery, $delivery->lines()->firstOrFail(), $item, $warehouse, $stock];
    }

    private function createPurchaseReceipt(): array
    {
        $item = Item::factory()->create();
        $supplier = Supplier::query()->create([
            'code' => 'SUP-RET',
            'name' => 'Supplier Retur',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-RET-P',
            'name' => 'Gudang Retur Pembelian',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::query()->create([
            'po_number' => 'PO-RET-001',
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-20',
            'status' => 'received',
            'total_amount' => '625.00',
        ]);
        $receipt = GoodsReceipt::query()->create([
            'gr_number' => 'GR-RET-001',
            'purchase_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'receipt_date' => '2026-10-21',
            'status' => 'posted',
        ]);
        $receiptLine = GoodsReceiptLine::query()->create([
            'goods_receipt_id' => $receipt->id,
            'item_id' => $item->id,
            'qty' => '5.0000',
            'unit_cost' => '125.0000',
        ]);
        InventoryTransaction::query()->create([
            'transaction_date' => '2026-10-21 00:00:00',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'in',
            'qty' => '5.0000',
            'unit_cost' => '125.0000',
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $receipt->id,
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '5.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '5.0000',
        ]);

        return [$receipt, $receiptLine, $stock];
    }
}
