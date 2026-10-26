<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\MasterPrice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Services\MasterPriceService;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class MasterPriceTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_latest_effective_start_date_is_used_after_previous_period_is_closed(): void
    {
        $item = Item::factory()->create();
        $oldPrice = $this->createPrice($item, '100.0000', '2026-10-01', '2026-10-16');
        $newPrice = $this->createPrice($item, '125.0000', '2026-10-17', null);
        $prices = app(MasterPriceService::class);

        $this->assertSame($oldPrice->id, $prices->priceForDate($item->id, '2026-10-16')?->id);
        $this->assertSame($newPrice->id, $prices->priceForDate($item->id, '2026-10-17')?->id);
        $this->assertSame($newPrice->id, $prices->priceForDate($item->id, '2026-10-30')?->id);
    }

    public function test_new_price_cannot_overlap_and_previous_price_must_be_closed(): void
    {
        $item = Item::factory()->create();
        $this->createPrice($item, '100.0000', '2026-10-01', null);

        $this->from(route('master-prices.create', ['priceType' => 'sell']))
            ->post(route('master-prices.store', ['priceType' => 'sell']), [
                'item_id' => $item->id,
                'price' => '125.0000',
                'start_date' => '2026-10-17',
                'end_date' => null,
            ])
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('master_prices', 1);
    }

    public function test_sell_and_buy_prices_have_independent_periods_and_lookups(): void
    {
        $item = Item::factory()->create();
        $supplier = Supplier::query()->create([
            'code' => 'SUP-PRICE',
            'name' => 'Supplier Price',
            'is_active' => true,
        ]);
        $sellPrice = $this->createPrice($item, '125.0000', '2026-10-01', null);
        $buyPrice = app(MasterPriceService::class)->create([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'price_type' => 'buy',
            'price' => '95.0000',
            'start_date' => '2026-10-01',
            'end_date' => null,
        ]);
        $prices = app(MasterPriceService::class);

        $this->assertSame($sellPrice->id, $prices->priceForDate($item->id, '2026-10-17')?->id);
        $this->assertSame($buyPrice->id, $prices->priceForDate($item->id, '2026-10-17', type: 'buy', supplierId: $supplier->id)?->id);
        $this->from(route('master-prices.create', ['priceType' => 'buy']))
            ->post(route('master-prices.store', ['priceType' => 'buy']), [
                'item_id' => $item->id,
                'supplier_id' => $supplier->id,
                'price_type' => 'buy',
                'price' => '99.0000',
                'start_date' => '2026-10-10',
                'end_date' => null,
            ])
            ->assertSessionHasErrors('start_date');
        $this->assertSame('125.0000', $this->get(route('sales-orders.price', [
            'item_id' => $item->id,
            'order_date' => '2026-10-17',
        ]))->assertOk()->json('price'));
        $this->assertSame('95.0000', $this->get(route('purchase-orders.price', [
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-17',
        ]))->assertOk()->assertJsonPath('price', '95.0000')->assertJsonPath('comparisons.0.supplier', 'Supplier Price')->json('price'));
    }

    public function test_sales_order_price_lookup_uses_order_date_and_does_not_expose_editable_price_input(): void
    {
        $item = Item::factory()->create(['item_type' => 'raw']);
        Supplier::query()->create(['code' => 'SUP-PO-FORM', 'name' => 'Supplier PO Form', 'is_active' => true]);
        $price = $this->createPrice($item, '345.6700', '2026-10-01', null);

        $this->get(route('sales-orders.price', [
            'item_id' => $item->id,
            'order_date' => '2026-10-17',
        ]))
            ->assertOk()
            ->assertJsonPath('price', '345.6700')
            ->assertJsonPath('start_date', '2026-10-01');

        $this->get(route('sales-orders.create'))
            ->assertOk()
            ->assertDontSee('name="lines[0][unit_price]"', false)
            ->assertSee('Master Harga');
        $this->get(route('purchase-orders.create'))
            ->assertOk()
            ->assertDontSee('name="lines[0][unit_price]"', false)
            ->assertSee('Daftarkan harga beli item untuk supplier');
    }

    public function test_same_item_has_supplier_specific_buy_prices_and_comparison_lookup(): void
    {
        $item = Item::factory()->create();
        $supplierA = Supplier::query()->create(['code' => 'SUP-A', 'name' => 'Supplier A', 'is_active' => true]);
        $supplierB = Supplier::query()->create(['code' => 'SUP-B', 'name' => 'Supplier B', 'is_active' => true]);
        $prices = app(MasterPriceService::class);
        $priceA = $prices->create([
            'item_id' => $item->id,
            'supplier_id' => $supplierA->id,
            'price_type' => 'buy',
            'price' => '120.0000',
            'start_date' => '2026-10-01',
        ]);
        $priceB = $prices->create([
            'item_id' => $item->id,
            'supplier_id' => $supplierB->id,
            'price_type' => 'buy',
            'price' => '100.0000',
            'start_date' => '2026-10-01',
        ]);

        $this->assertSame($priceA->id, $prices->priceForDate($item->id, '2026-10-21', type: 'buy', supplierId: $supplierA->id)?->id);
        $this->assertSame($priceB->id, $prices->priceForDate($item->id, '2026-10-21', type: 'buy', supplierId: $supplierB->id)?->id);
        $this->assertSame(2, $prices->buyPricesForDate($item->id, '2026-10-21')->count());
        $this->assertSame('100.0000', $this->get(route('purchase-orders.price', [
            'item_id' => $item->id,
            'supplier_id' => $supplierA->id,
            'order_date' => '2026-10-21',
        ]))->assertOk()->json('comparisons.0.price'));

        $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplierA->id,
            'order_date' => '2026-10-21',
            'lines' => [['item_id' => $item->id, 'qty' => '2.00']],
        ])->assertRedirect();
        $this->assertDatabaseHas('purchase_order_lines', [
            'item_id' => $item->id,
            'master_price_id' => $priceA->id,
            'unit_price' => '120.0000',
        ]);
    }

    public function test_buy_price_requires_a_supplier(): void
    {
        $item = Item::factory()->create();

        $this->from(route('master-prices.create', ['priceType' => 'buy']))
            ->post(route('master-prices.store', ['priceType' => 'buy']), [
                'item_id' => $item->id,
                'price_type' => 'buy',
                'price' => '100.0000',
                'start_date' => '2026-10-01',
            ])
            ->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseCount('master_prices', 0);
    }

    public function test_sell_and_buy_masters_are_separate_and_cannot_edit_each_others_prices(): void
    {
        $item = Item::factory()->create();
        $supplier = Supplier::query()->create(['code' => 'SUP-SEP', 'name' => 'Supplier Separate', 'is_active' => true]);
        $sellPrice = $this->createPrice($item, '125.0000', '2026-10-01', null);
        $buyPrice = app(MasterPriceService::class)->create([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'price_type' => 'buy',
            'price' => '95.0000',
            'start_date' => '2026-10-01',
        ]);

        $this->get(route('master-prices.index', ['priceType' => 'sell']))
            ->assertOk()
            ->assertSee('Master Harga Jual')
            ->assertSee('125,0000')
            ->assertDontSee($supplier->name)
            ->assertDontSee('name="price_type"', false);

        $this->get(route('master-prices.index', ['priceType' => 'buy']))
            ->assertOk()
            ->assertSee('Master Harga Beli')
            ->assertSee($supplier->name)
            ->assertSee('95,0000');

        $this->get(route('master-prices.create', ['priceType' => 'sell']))
            ->assertOk()
            ->assertDontSee('name="supplier_id"', false)
            ->assertDontSee('name="price_type"', false);
        $this->get(route('master-prices.create', ['priceType' => 'buy']))
            ->assertOk()
            ->assertSee('name="supplier_id"', false)
            ->assertDontSee('name="price_type"', false);

        $this->get(route('master-prices.edit', ['priceType' => 'sell', 'masterPrice' => $buyPrice]))
            ->assertNotFound();
        $this->get(route('master-prices.edit', ['priceType' => 'buy', 'masterPrice' => $sellPrice]))
            ->assertNotFound();
    }

    public function test_sales_order_saves_master_price_snapshot_and_price_cannot_be_changed_after_use(): void
    {
        $item = Item::factory()->create();
        $price = $this->createPrice($item, '345.6700', '2026-10-01', null);
        $order = app(SalesOrderService::class)->create([
            'customer_id' => Customer::factory()->create()->id,
            'order_date' => '2026-10-17',
            'lines' => [['item_id' => $item->id, 'qty' => '2']],
        ]);

        $this->assertDatabaseHas('sales_order_lines', [
            'sales_order_id' => $order->id,
            'master_price_id' => $price->id,
            'unit_price' => '345.6700',
        ]);
        $this->assertSame('691.34', $order->total_amount);

        $this->from(route('master-prices.edit', ['priceType' => 'sell', 'masterPrice' => $price]))
            ->put(route('master-prices.update', ['priceType' => 'sell', 'masterPrice' => $price]), [
                'item_id' => $item->id,
                'price' => '999.0000',
                'start_date' => '2026-10-01',
                'end_date' => null,
            ])
            ->assertSessionHasErrors('price');

        $this->assertSame('345.6700', $price->fresh()->price);
        $this->assertSame('345.6700', SalesOrder::query()->with('lines')->findOrFail($order->id)->lines->first()->unit_price);
    }

    private function createPrice(Item $item, string $amount, string $start, ?string $end): MasterPrice
    {
        return app(MasterPriceService::class)->create([
            'item_id' => $item->id,
            'price' => $amount,
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
