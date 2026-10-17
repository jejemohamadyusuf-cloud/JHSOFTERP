<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_sales_order_draft_with_lines_and_total(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();

        $response = $this->post(route('sales-orders.store'), [
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'notes' => 'Pesanan contoh',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '2.5000', 'unit_price' => '12500.0000'],
            ],
        ]);

        $order = SalesOrder::query()->firstOrFail();

        $response->assertRedirect(route('sales-orders.show', $order));
        $this->assertSame('draft', $order->status);
        $this->assertSame('31250.00', $order->total_amount);
        $this->assertSame('SO-20261017-', substr($order->so_number, 0, 12));
        $this->assertDatabaseHas('sales_order_lines', [
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'unit_id' => $item->unit_id,
            'qty' => '2.5000',
        ]);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_sales_order_requires_valid_customer_and_positive_quantity(): void
    {
        $inactiveCustomer = Customer::factory()->create(['is_active' => false]);
        $item = Item::factory()->create();

        $response = $this->from(route('sales-orders.create'))->post(route('sales-orders.store'), [
            'customer_id' => $inactiveCustomer->id,
            'order_date' => '2026-10-17',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '0', 'unit_price' => '100'],
            ],
        ]);

        $response->assertSessionHasErrors(['customer_id', 'lines.0.qty']);
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_confirming_sales_order_changes_status_without_mutating_inventory(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $order = app(SalesOrderService::class)->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '1', 'unit_price' => '50000'],
            ],
        ]);

        $response = $this->post(route('sales-orders.confirm', $order));

        $response->assertRedirect(route('sales-orders.show', $order));
        $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_draft_can_be_edited_but_confirmed_order_cannot_be_changed(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $order = app(SalesOrderService::class)->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '1', 'unit_price' => '10000'],
            ],
        ]);

        $updateResponse = $this->put(route('sales-orders.update', $order), [
            'customer_id' => $customer->id,
            'order_date' => '2026-10-18',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '3', 'unit_price' => '12500'],
            ],
        ]);

        $updateResponse->assertRedirect(route('sales-orders.show', $order));
        $order->refresh();
        $this->assertSame('37500.00', $order->total_amount);
        $this->assertSame('3.0000', $order->lines()->firstOrFail()->qty);

        $this->post(route('sales-orders.confirm', $order));
        $this->put(route('sales-orders.update', $order), [
            'customer_id' => $customer->id,
            'order_date' => '2026-10-19',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '4', 'unit_price' => '12500'],
            ],
        ])->assertSessionHasErrors('status');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('37500.00', $order->fresh()->total_amount);
    }
}
