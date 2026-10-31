<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\GeneralJournalEntry;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class DeliveryInvoiceTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_delivery_invoice_and_partial_payments_post_the_expected_balances(): void
    {
        [$salesOrder, $line, $item, $warehouse, $stock] = $this->createSalesSetup();

        $this->post(route('deliveries.store'), [
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => '2026-10-20',
            'lines' => [[
                'sales_order_line_id' => $line->id,
                'qty' => '3.00',
            ]],
        ])->assertRedirect();

        $delivery = Delivery::query()->firstOrFail();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('7.0000', $stock->fresh()->qty_available);
        $this->get(route('deliveries.show', $delivery))->assertOk();
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'out',
            'reference_id' => $delivery->lines()->firstOrFail()->id,
        ]);

        $this->post(route('deliveries.invoice.store', $delivery), [
            'invoice_date' => '2026-10-21',
            'due_date' => '2026-11-20',
        ])->assertRedirect();

        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('300.00', $invoice->total_amount);
        $this->assertSame('3.0000', $invoice->lines()->firstOrFail()->qty);
        $this->assertDatabaseHas('general_journal_entries', [
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
            'reference' => $invoice->invoice_number,
        ]);
        $this->assertDatabaseHas('general_journal_lines', [
            'chart_of_account_id' => ChartOfAccount::query()->where('code', '1120')->value('id'),
            'debit_amount' => '300.00',
        ]);
        $this->get(route('invoices.show', $invoice))->assertOk();
        $this->post(route('deliveries.invoice.store', $delivery), [
            'invoice_date' => '2026-10-21',
        ])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('invoices', 1);

        $this->post(route('invoices.payments.store', $invoice), [
            'payment_date' => '2026-10-22',
            'amount' => '200.00',
            'payment_method' => 'transfer',
            'reference' => 'TRX-001',
        ])->assertRedirect();
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame('200.00', $invoice->fresh()->paid_amount);
        $this->assertDatabaseHas('general_journal_entries', [
            'source_type' => Payment::class,
            'source_id' => $invoice->payments()->firstOrFail()->id,
        ]);

        $this->post(route('invoices.payments.store', $invoice), [
            'payment_date' => '2026-10-23',
            'amount' => '100.00',
            'payment_method' => 'cash',
        ])->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('2', (string) $invoice->payments()->count());

        $this->post(route('invoices.payments.store', $invoice), [
            'payment_date' => '2026-10-24',
            'amount' => '1.00',
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('amount');
        $this->assertSame(2, $invoice->payments()->count());
        $this->assertSame(1, InventoryTransaction::query()->count());
    }

    public function test_delivery_cannot_ship_more_than_the_sales_order_or_available_stock(): void
    {
        [$salesOrder, $line, , $warehouse, $stock] = $this->createSalesSetup();

        $this->from(route('deliveries.create', ['sales_order_id' => $salesOrder->id]))
            ->post(route('deliveries.store'), [
                'sales_order_id' => $salesOrder->id,
                'warehouse_id' => $warehouse->id,
                'delivery_date' => '2026-10-20',
                'lines' => [[
                    'sales_order_line_id' => $line->id,
                    'qty' => '6.00',
                ]],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertSame('10.0000', $stock->fresh()->qty_available);

        $this->get(route('deliveries.create'))->assertOk();
        $this->get(route('deliveries.create', ['sales_order_id' => $salesOrder->id]))->assertOk();
        $this->get(route('invoices.index'))->assertOk();
    }

    public function test_delivery_cannot_exceed_stock_and_rolls_back_the_delivery(): void
    {
        [$salesOrder, $line, , $warehouse, $stock] = $this->createSalesSetup();
        $stock->update(['qty_on_hand' => '2.0000', 'qty_available' => '2.0000']);

        $this->from(route('deliveries.create', ['sales_order_id' => $salesOrder->id]))
            ->post(route('deliveries.store'), [
                'sales_order_id' => $salesOrder->id,
                'warehouse_id' => $warehouse->id,
                'delivery_date' => '2026-10-20',
                'lines' => [[
                    'sales_order_line_id' => $line->id,
                    'qty' => '3.00',
                ]],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame('2.0000', $stock->fresh()->qty_available);
    }

    public function test_delivery_posts_cost_of_goods_sold_from_inventory_average_cost(): void
    {
        [$salesOrder, $line, $item, $warehouse] = $this->createSalesSetup();
        InventoryTransaction::query()->create([
            'transaction_date' => '2026-10-19 00:00:00',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'in',
            'qty' => '10.0000',
            'unit_cost' => '50.0000',
            'reference_type' => 'OpeningBalance',
            'reference_id' => 1,
        ]);

        $this->post(route('deliveries.store'), [
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => '2026-10-20',
            'lines' => [[
                'sales_order_line_id' => $line->id,
                'qty' => '3.00',
            ]],
        ])->assertRedirect();

        $delivery = Delivery::query()->firstOrFail();
        $journal = GeneralJournalEntry::query()
            ->where('source_type', Delivery::class)
            ->where('source_id', $delivery->id)
            ->with('lines.account')
            ->firstOrFail();

        $this->assertSame('150.00', $journal->lines->firstWhere('account.code', '5100')->debit_amount);
        $this->assertSame('150.00', $journal->lines->firstWhere('account.code', '1133')->credit_amount);
        $deliveryLine = $delivery->lines()->firstOrFail();
        $this->assertSame('50.0000', InventoryTransaction::query()
            ->where('reference_type', DeliveryLine::class)
            ->where('reference_id', $deliveryLine->id)
            ->firstOrFail()
            ->unit_cost);
    }

    /**
     * @return array{SalesOrder, SalesOrderLine, Item, Warehouse, StockLevel}
     */
    private function createSalesSetup(): array
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create(['item_type' => 'finished']);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-DEL',
            'name' => 'Gudang Pengiriman',
            'is_active' => true,
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '10.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '10.0000',
        ]);
        $salesOrder = SalesOrder::query()->create([
            'so_number' => 'SO261000998',
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'status' => 'confirmed',
            'total_amount' => '500.00',
        ]);
        $line = SalesOrderLine::query()->create([
            'sales_order_id' => $salesOrder->id,
            'item_id' => $item->id,
            'qty' => '5.0000',
            'unit_price' => '100.0000',
            'unit_id' => $item->unit_id,
        ]);

        return [$salesOrder, $line, $item, $warehouse, $stock];
    }
}
