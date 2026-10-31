<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\GeneralJournalEntry;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\TransactionCancellation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class TransactionCancellationTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_unposted_sales_order_can_be_soft_deleted_with_a_cancellation_reason(): void
    {
        $order = SalesOrder::query()->create([
            'so_number' => 'SO-CANCEL-001',
            'customer_id' => Customer::factory()->create()->id,
            'order_date' => '2026-10-25',
            'status' => 'draft',
            'total_amount' => '100.00',
        ]);

        $this->post(route('transactions.cancel', ['type' => 'sales-order', 'id' => $order->id]), [
            'cancellation_reason' => 'Pesanan dibatalkan pelanggan',
        ])->assertRedirect();

        $this->assertSoftDeleted('sales_orders', ['id' => $order->id]);
        $this->assertDatabaseHas('transaction_cancellations', [
            'transaction_type' => 'sales-order',
            'transaction_id' => $order->id,
            'reason' => 'Pesanan dibatalkan pelanggan',
        ]);
    }

    public function test_cancelling_stock_adjustment_reverses_the_stock_movement(): void
    {
        $item = Item::factory()->create();
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-CANCEL',
            'name' => 'Gudang Pembatalan',
            'is_active' => true,
        ]);
        $adjustment = StockAdjustment::query()->create([
            'adjustment_number' => 'AO-CANCEL-001',
            'direction' => 'out',
            'adjustment_date' => '2026-10-25',
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '3.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '3.0000',
        ]);
        InventoryTransaction::query()->create([
            'transaction_date' => '2026-10-25 00:00:00',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'out',
            'qty' => '2.0000',
            'unit_cost' => '10.0000',
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
        ]);
        $account = ChartOfAccount::query()->where('is_header', false)->where('is_active', true)->firstOrFail();
        $journal = GeneralJournalEntry::query()->create([
            'journal_number' => 'GJ-AO-CANCEL-001',
            'journal_date' => '2026-10-25',
            'description' => 'Jurnal adjustment',
            'source_type' => StockAdjustment::class,
            'source_id' => $adjustment->id,
            'created_by' => auth()->id(),
            'posted_at' => now(),
        ]);
        $journal->lines()->create([
            'chart_of_account_id' => $account->id,
            'debit_amount' => '20.00',
            'credit_amount' => '0.00',
        ]);
        $journal->lines()->create([
            'chart_of_account_id' => $account->id,
            'debit_amount' => '0.00',
            'credit_amount' => '20.00',
        ]);

        $this->post(route('transactions.cancel', ['type' => 'stock-adjustment', 'id' => $adjustment->id]), [
            'cancellation_reason' => 'Koreksi adjustment salah',
        ])->assertRedirect();

        $this->assertSoftDeleted('stock_adjustments', ['id' => $adjustment->id]);
        $this->assertSame('5.0000', $stock->fresh()->qty_on_hand);
        $this->assertDatabaseHas('inventory_transactions', [
            'reference_type' => TransactionCancellation::class,
            'transaction_type' => 'in',
            'qty' => '2.0000',
        ]);
        $cancellation = TransactionCancellation::query()
            ->where('transaction_type', 'stock-adjustment')
            ->where('transaction_id', $adjustment->id)
            ->firstOrFail();
        $this->assertDatabaseHas('general_journal_entries', [
            'id' => $journal->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('general_journal_entries', [
            'source_type' => TransactionCancellation::class,
            'source_id' => $cancellation->id,
        ]);
    }

    public function test_cancelling_delivery_restores_stock(): void
    {
        $item = Item::factory()->create();
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-DEL-CANCEL',
            'name' => 'Gudang Delivery Batal',
            'is_active' => true,
        ]);
        $order = SalesOrder::query()->create([
            'so_number' => 'SO-DEL-CANCEL',
            'customer_id' => Customer::factory()->create()->id,
            'order_date' => '2026-10-25',
            'status' => 'confirmed',
            'total_amount' => '300.00',
        ]);
        $orderLine = SalesOrderLine::query()->create([
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => '3.0000',
            'unit_price' => '100.0000',
            'unit_id' => $item->unit_id,
        ]);
        $delivery = Delivery::query()->create([
            'delivery_number' => 'DO-DEL-CANCEL',
            'sales_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => '2026-10-25',
            'status' => 'delivered',
        ]);
        $deliveryLine = DeliveryLine::query()->create([
            'delivery_id' => $delivery->id,
            'sales_order_line_id' => $orderLine->id,
            'item_id' => $item->id,
            'qty' => '3.0000',
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '2.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '2.0000',
        ]);
        InventoryTransaction::query()->create([
            'transaction_date' => '2026-10-25 00:00:00',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'out',
            'qty' => '3.0000',
            'unit_cost' => '10.0000',
            'reference_type' => DeliveryLine::class,
            'reference_id' => $deliveryLine->id,
        ]);

        $this->post(route('transactions.cancel', ['type' => 'delivery', 'id' => $delivery->id]), [
            'cancellation_reason' => 'Pengiriman salah dibuat',
        ])->assertRedirect();

        $this->assertSoftDeleted('deliveries', ['id' => $delivery->id]);
        $this->assertSame('5.0000', $stock->fresh()->qty_on_hand);
    }

    public function test_cancelling_receiving_reverses_stock_and_purchase_order_quantity(): void
    {
        $item = Item::factory()->create();
        $supplier = Supplier::query()->create([
            'code' => 'SUP-CANCEL',
            'name' => 'Supplier Pembatalan',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-GR-CANCEL',
            'name' => 'Gudang Receiving Batal',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::query()->create([
            'po_number' => 'PO-GR-CANCEL',
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-25',
            'status' => 'received',
            'total_amount' => '50.00',
        ]);
        $orderLine = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => '5.0000',
            'qty_received' => '5.0000',
            'unit_price' => '10.0000',
            'unit_id' => $item->unit_id,
        ]);
        $receipt = GoodsReceipt::query()->create([
            'gr_number' => 'GR-CANCEL',
            'purchase_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'receipt_date' => '2026-10-25',
            'status' => 'posted',
        ]);
        GoodsReceiptLine::query()->create([
            'goods_receipt_id' => $receipt->id,
            'purchase_order_line_id' => $orderLine->id,
            'item_id' => $item->id,
            'qty' => '5.0000',
            'unit_cost' => '10.0000',
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '5.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '5.0000',
        ]);
        InventoryTransaction::query()->create([
            'transaction_date' => '2026-10-25 00:00:00',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'in',
            'qty' => '5.0000',
            'unit_cost' => '10.0000',
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $receipt->id,
        ]);

        $this->post(route('transactions.cancel', ['type' => 'receiving', 'id' => $receipt->id]), [
            'cancellation_reason' => 'Penerimaan salah input',
        ])->assertRedirect();

        $this->assertSoftDeleted('goods_receipts', ['id' => $receipt->id]);
        $this->assertSame('0.0000', $stock->fresh()->qty_on_hand);
        $this->assertSame('0.0000', $orderLine->fresh()->qty_received);
        $this->assertSame('open', $order->fresh()->status);
    }

    public function test_cancelling_sales_order_is_blocked_while_active_delivery_exists(): void
    {
        $order = SalesOrder::query()->create([
            'so_number' => 'SO-CANCEL-002',
            'customer_id' => Customer::factory()->create()->id,
            'order_date' => '2026-10-25',
            'status' => 'confirmed',
            'total_amount' => '100.00',
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-CANCEL-2',
            'name' => 'Gudang Pembatalan 2',
            'is_active' => true,
        ]);
        $delivery = Delivery::query()->create([
            'delivery_number' => 'DO-CANCEL-001',
            'sales_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => '2026-10-25',
            'status' => 'delivered',
        ]);

        $this->from(route('sales-orders.index'))
            ->post(route('transactions.cancel', ['type' => 'sales-order', 'id' => $order->id]), [
                'cancellation_reason' => 'Pesanan dibatalkan pelanggan',
            ])
            ->assertSessionHasErrors('transaction');

        $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id, 'deleted_at' => null]);
    }

    public function test_cancelling_manual_journal_posts_a_balanced_reversal_and_keeps_the_original(): void
    {
        $account = ChartOfAccount::query()->where('is_header', false)->where('is_active', true)->firstOrFail();
        $journal = GeneralJournalEntry::query()->create([
            'journal_number' => 'GJ-CANCEL-001',
            'journal_date' => '2026-10-25',
            'reference' => 'TEST',
            'description' => 'Jurnal untuk uji pembatalan',
            'created_by' => auth()->id(),
            'posted_at' => now(),
        ]);
        $journal->lines()->create([
            'chart_of_account_id' => $account->id,
            'description' => 'Debit',
            'debit_amount' => '100.00',
            'credit_amount' => '0.00',
        ]);
        $journal->lines()->create([
            'chart_of_account_id' => $account->id,
            'description' => 'Kredit',
            'debit_amount' => '0.00',
            'credit_amount' => '100.00',
        ]);

        $this->post(route('transactions.cancel', ['type' => 'journal', 'id' => $journal->id]), [
            'cancellation_reason' => 'Koreksi jurnal salah',
        ])->assertRedirect();

        $cancellation = TransactionCancellation::query()
            ->where('transaction_type', 'journal')
            ->where('transaction_id', $journal->id)
            ->firstOrFail();
        $reversal = GeneralJournalEntry::query()
            ->where('source_type', TransactionCancellation::class)
            ->where('source_id', $cancellation->id)
            ->with('lines')
            ->firstOrFail();

        $this->assertDatabaseHas('general_journal_entries', ['id' => $journal->id, 'deleted_at' => null]);
        $this->assertEquals(100, $reversal->lines->sum('credit_amount'));
        $this->assertEquals(100, $reversal->lines->sum('debit_amount'));
    }
}
