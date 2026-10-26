<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MasterPrice;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class SalesOrderTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_user_can_create_sales_order_draft_with_lines_and_total(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $this->createPrice($item, '12500.0000');

        $response = $this->post(route('sales-orders.store'), [
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'notes' => 'Pesanan contoh',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '2.50', 'unit_price' => '1.0000'],
            ],
        ]);

        $order = SalesOrder::query()->firstOrFail();

        $response->assertRedirect(route('sales-orders.show', $order));
        $this->assertSame('draft', $order->status);
        $this->assertSame('31250.00', $order->total_amount);
        $this->assertSame('SO261000001', $order->so_number);
        $this->assertDatabaseHas('sales_order_lines', [
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'unit_id' => $item->unit_id,
            'qty' => '2.50',
        ]);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_sales_order_number_increments_and_restarts_for_a_new_month(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $this->createPrice($item, '100.0000');
        $service = app(SalesOrderService::class);

        $first = $service->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'lines' => [['item_id' => $item->id, 'qty' => '1']],
        ]);
        $second = $service->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-10-18',
            'lines' => [['item_id' => $item->id, 'qty' => '1']],
        ]);
        $nextMonth = $service->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-11-01',
            'lines' => [['item_id' => $item->id, 'qty' => '1']],
        ]);

        $this->assertSame('SO261000001', $first->so_number);
        $this->assertSame('SO261000002', $second->so_number);
        $this->assertSame('SO261100001', $nextMonth->so_number);
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

    public function test_sales_order_rejects_quantities_with_more_than_two_decimal_places(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $this->createPrice($item, '100.0000');

        $this->from(route('sales-orders.create'))
            ->post(route('sales-orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => '2026-10-17',
                'lines' => [['item_id' => $item->id, 'qty' => '1.234']],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_sales_order_offers_and_accepts_all_active_item_types(): void
    {
        $finishedItem = Item::factory()->create(['item_type' => 'finished']);
        $rawItem = Item::factory()->create(['item_type' => 'raw']);
        $semiFinishedItem = Item::factory()->create(['item_type' => 'semi']);
        $customer = Customer::factory()->create();
        $this->createPrice($finishedItem, '100.0000');
        $this->createPrice($rawItem, '200.0000');
        $this->createPrice($semiFinishedItem, '300.0000');

        $this->get(route('sales-orders.create'))
            ->assertOk()
            ->assertSee($finishedItem->code)
            ->assertSee($rawItem->code)
            ->assertSee($semiFinishedItem->code);

        $response = $this->post(route('sales-orders.store'), [
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'lines' => [
                ['item_id' => $rawItem->id, 'qty' => '1'],
                ['item_id' => $semiFinishedItem->id, 'qty' => '2'],
            ],
        ]);

        $order = SalesOrder::query()->firstOrFail();
        $response->assertRedirect(route('sales-orders.show', $order));
        $this->assertSame('800.00', $order->total_amount);
        $this->assertSame(2, $order->lines()->count());
    }

    public function test_confirming_sales_order_changes_status_without_mutating_inventory(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $this->createPrice($item, '50000.0000');
        $order = app(SalesOrderService::class)->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '1'],
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
        $this->createPrice($item, '12500.0000');
        $order = app(SalesOrderService::class)->create([
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '1'],
            ],
        ]);

        $updateResponse = $this->put(route('sales-orders.update', $order), [
            'customer_id' => $customer->id,
            'order_date' => '2026-10-18',
            'lines' => [
                ['item_id' => $item->id, 'qty' => '3'],
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
                ['item_id' => $item->id, 'qty' => '4'],
            ],
        ])->assertSessionHasErrors('status');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('37500.00', $order->fresh()->total_amount);
    }

    public function test_order_rejects_item_without_effective_master_price(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();

        $this->from(route('sales-orders.create'))
            ->post(route('sales-orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => '2026-10-17',
                'lines' => [['item_id' => $item->id, 'qty' => '1']],
            ])
            ->assertSessionHasErrors('lines.0.item_id');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    private function createPrice(Item $item, string $amount): MasterPrice
    {
        return MasterPrice::query()->create([
            'item_id' => $item->id,
            'price' => $amount,
            'start_date' => '2026-10-01',
        ]);
    }
}
