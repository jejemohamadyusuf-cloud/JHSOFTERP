<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemUnitConversion;
use App\Models\StockLevel;
use App\Models\StockTake;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class StockTakeTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_stock_quantities_are_displayed_in_package_units_but_stored_as_base_units(): void
    {
        $baseUnit = Unit::factory()->create(['code' => 'PCS', 'name' => 'Pieces']);
        $packUnit = Unit::factory()->create(['code' => 'PACK', 'name' => 'Pack']);
        $cartonUnit = Unit::factory()->create(['code' => 'DUS', 'name' => 'Dus']);
        $item = Item::factory()->create(['unit_id' => $baseUnit->id]);
        ItemUnitConversion::query()->create(['item_id' => $item->id, 'unit_id' => $packUnit->id, 'base_units' => 10]);
        ItemUnitConversion::query()->create(['item_id' => $item->id, 'unit_id' => $cartonUnit->id, 'base_units' => 100]);
        $warehouse = $this->createWarehouse();
        StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '193.0000',
            'qty_reserved' => '0',
            'qty_available' => '193.0000',
        ]);

        $this->get(route('stock.index'))
            ->assertOk()
            ->assertSee('1 DUS · 9 PACK · 3 PCS');
        $this->get(route('stock-takes.position').'?'.http_build_query([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
        ]))
            ->assertOk()
            ->assertJsonPath('formatted_qty', '1 DUS · 9 PACK · 3 PCS')
            ->assertJsonPath('system_qty', '193.0000');
        $this->assertSame('193.0000', StockLevel::query()->firstOrFail()->qty_on_hand);
    }

    public function test_stock_take_routes_are_restricted_to_inventory_and_admin_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales']))
            ->get(route('stock-takes.create'))
            ->assertForbidden();
    }

    public function test_stock_take_reconciles_physical_count_and_records_discrepancy(): void
    {
        $item = Item::factory()->create();
        $warehouse = $this->createWarehouse();
        StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '200.0000',
            'qty_reserved' => '20.0000',
            'qty_available' => '180.0000',
        ]);

        $this->get(route('stock-takes.create'))
            ->assertOk()
            ->assertSee('stock-take-table-wrap')
            ->assertSee('discrepancy-qty')
            ->assertSee('disabled', false);

        $this->post(route('stock-takes.store'), [
            'stock_take_date' => '2026-10-26',
            'notes' => 'Stock opname rak A',
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'location_id' => '',
                'system_qty' => '200.0000',
                'physical_qty' => '193.0000',
            ]],
        ])->assertRedirect(route('stock.index'));

        $stockTake = StockTake::query()->with('lines')->firstOrFail();
        $this->assertSame('ST261000001', $stockTake->stock_take_number);
        $this->assertSame('200.0000', $stockTake->lines->first()->system_qty);
        $this->assertSame('193.0000', $stockTake->lines->first()->physical_qty);
        $this->assertSame('-7.0000', $stockTake->lines->first()->discrepancy_qty);
        $this->assertSame('193.0000', StockLevel::query()->firstOrFail()->qty_on_hand);
        $this->assertSame('173.0000', StockLevel::query()->firstOrFail()->qty_available);
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'out',
            'qty' => '7.0000',
            'reference_type' => StockTake::class,
            'reference_id' => $stockTake->id,
        ]);
        $this->assertInstanceOf(StockTake::class, InventoryTransaction::query()->firstOrFail()->reference);
    }

    public function test_stock_take_adds_physical_quantity_when_there_is_no_system_balance(): void
    {
        $baseUnit = Unit::factory()->create(['code' => 'PCS', 'name' => 'Pieces']);
        $packUnit = Unit::factory()->create(['code' => 'PACK', 'name' => 'Pack']);
        $cartonUnit = Unit::factory()->create(['code' => 'DUS', 'name' => 'Dus']);
        $item = Item::factory()->create(['unit_id' => $baseUnit->id]);
        ItemUnitConversion::query()->create(['item_id' => $item->id, 'unit_id' => $packUnit->id, 'base_units' => 10]);
        ItemUnitConversion::query()->create(['item_id' => $item->id, 'unit_id' => $cartonUnit->id, 'base_units' => 100]);
        $warehouse = $this->createWarehouse();

        $this->post(route('stock-takes.store'), [
            'stock_take_date' => '2026-10-26',
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'system_qty' => '0',
                'physical_qty' => '193',
            ]],
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('stock_take_lines', [
            'item_id' => $item->id,
            'system_qty' => '0.0000',
            'physical_qty' => '193.0000',
            'discrepancy_qty' => '193.0000',
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'item_id' => $item->id,
            'qty_on_hand' => '193.0000',
            'qty_available' => '193.0000',
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'in',
            'qty' => '193.0000',
            'reference_type' => StockTake::class,
        ]);
    }

    public function test_stock_take_rejects_stale_system_balance_and_counts_below_reserved_stock(): void
    {
        $item = Item::factory()->create();
        $warehouse = $this->createWarehouse();
        StockLevel::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '10.0000',
            'qty_reserved' => '3.0000',
            'qty_available' => '7.0000',
        ]);

        $payload = [
            'stock_take_date' => '2026-10-26',
            'lines' => [[
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'system_qty' => '9.0000',
                'physical_qty' => '5.0000',
            ]],
        ];
        $this->from(route('stock-takes.create'))
            ->post(route('stock-takes.store'), $payload)
            ->assertSessionHasErrors('lines.0.physical_qty');
        $this->assertDatabaseCount('stock_takes', 0);
        $this->assertSame('10.0000', StockLevel::query()->firstOrFail()->qty_on_hand);

        $payload['lines'][0]['system_qty'] = '10.0000';
        $payload['lines'][0]['physical_qty'] = '2.0000';
        $this->from(route('stock-takes.create'))
            ->post(route('stock-takes.store'), $payload)
            ->assertSessionHasErrors('lines.0.physical_qty');
        $this->assertDatabaseCount('stock_takes', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_item_form_saves_and_validates_nested_package_conversion(): void
    {
        $baseUnit = Unit::factory()->create(['code' => 'PCS', 'name' => 'Pieces']);
        $packUnit = Unit::factory()->create(['code' => 'PACK', 'name' => 'Pack']);
        $cartonUnit = Unit::factory()->create(['code' => 'DUS', 'name' => 'Dus']);

        $this->post(route('master-data.store', ['master' => 'items']), [
            'code' => 'CONVERTED-ITEM',
            'name' => 'Barang Konversi',
            'item_type' => 'finished',
            'unit_id' => $baseUnit->id,
            'is_active' => '1',
            'conversions' => [
                ['unit_id' => $packUnit->id, 'base_units' => 10],
                ['unit_id' => $cartonUnit->id, 'base_units' => 100],
            ],
        ])->assertRedirect(route('master-data.index', 'items'));

        $item = Item::query()->where('code', 'CONVERTED-ITEM')->firstOrFail();
        $this->assertSame([100, 10], $item->unitConversions()->orderByDesc('base_units')->pluck('base_units')->all());

        $this->from(route('master-data.edit', ['master' => 'items', 'record' => $item->id]))
            ->put(route('master-data.update', ['master' => 'items', 'record' => $item->id]), [
                'code' => 'CONVERTED-ITEM',
                'name' => 'Barang Konversi',
                'item_type' => 'finished',
                'unit_id' => $baseUnit->id,
                'is_active' => '1',
                'conversions' => [
                    ['unit_id' => $packUnit->id, 'base_units' => 10],
                    ['unit_id' => $cartonUnit->id, 'base_units' => 95],
                ],
            ])->assertSessionHasErrors('conversions.1.base_units');
        $this->assertSame([100, 10], $item->unitConversions()->orderByDesc('base_units')->pluck('base_units')->all());
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::query()->create([
            'code' => 'WH-TAKE',
            'name' => 'Gudang Stock Take',
            'is_active' => true,
        ]);
    }
}
