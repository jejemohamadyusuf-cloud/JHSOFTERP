<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomLine;
use App\Models\Customer;
use App\Models\FinishedGoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WorkOrderTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_planned_work_order_from_a_confirmed_sales_order_line(): void
    {
        [$salesOrderLine, $bom, $warehouse] = $this->createProductionSetup();

        $response = $this->post(route('work-orders.store'), [
            'sales_order_line_id' => $salesOrderLine->id,
            'bom_id' => $bom->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '3.50',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-25',
            'notes' => 'Produksi sesuai pesanan',
        ]);

        $workOrder = WorkOrder::query()->firstOrFail();
        $response->assertRedirect(route('work-orders.show', $workOrder));
        $this->assertSame('planned', $workOrder->status);
        $this->assertSame('WO'.now()->format('ym').'00001', $workOrder->wo_number);
        $this->assertSame($salesOrderLine->sales_order_id, $workOrder->sales_order_id);
        $this->assertSame($salesOrderLine->id, $workOrder->sales_order_line_id);
        $this->assertSame($salesOrderLine->item_id, $workOrder->item_id);
        $this->assertSame('3.5000', $workOrder->qty_planned);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_work_order_form_can_be_opened_from_a_specific_sales_order(): void
    {
        [$salesOrderLine] = $this->createProductionSetup();
        $otherOrder = SalesOrder::query()->create([
            'so_number' => 'SO261001000',
            'customer_id' => $salesOrderLine->salesOrder->customer_id,
            'order_date' => '2026-10-17',
            'status' => 'confirmed',
            'total_amount' => '100.00',
        ]);
        $otherLine = SalesOrderLine::query()->create([
            'sales_order_id' => $otherOrder->id,
            'item_id' => $salesOrderLine->item_id,
            'qty' => '5.0000',
            'unit_price' => '10.0000',
            'unit_id' => $salesOrderLine->unit_id,
        ]);

        $this->get(route('work-orders.create', ['sales_order_id' => $salesOrderLine->sales_order_id]))
            ->assertOk()
            ->assertSee('value="'.$salesOrderLine->id.'"', false)
            ->assertDontSee('value="'.$otherLine->id.'"', false);
    }

    public function test_user_can_create_a_planned_make_to_stock_work_order(): void
    {
        $item = Item::factory()->create(['item_type' => 'finished']);
        $bom = Bom::query()->create([
            'item_id' => $item->id,
            'version' => '1',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-MTS',
            'name' => 'Gudang Persediaan',
            'is_active' => true,
        ]);

        $this->get(route('work-orders.create'))
            ->assertOk()
            ->assertSee('Make to Stock — untuk persediaan')
            ->assertSee('Barang jadi untuk stok');

        $this->post(route('work-orders.store'), [
            'production_type' => 'make_to_stock',
            'item_id' => $item->id,
            'bom_id' => $bom->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '12.00',
            'notes' => 'Produksi untuk stok',
        ])->assertRedirect();

        $workOrder = WorkOrder::query()->firstOrFail();
        $this->assertSame('make_to_stock', $workOrder->production_type);
        $this->assertNull($workOrder->sales_order_id);
        $this->assertNull($workOrder->sales_order_line_id);
        $this->assertSame($item->id, $workOrder->item_id);
        $this->assertSame('12.0000', $workOrder->qty_planned);
        $this->assertDatabaseCount('inventory_transactions', 0);

        $this->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Make to Stock')
            ->assertSee('Persediaan');
    }

    public function test_production_material_issue_qc_and_finished_goods_receipt_update_inventory(): void
    {
        [$salesOrderLine, $bom, $warehouse] = $this->createProductionSetup();
        $component = Item::factory()->create(['item_type' => 'raw']);
        BomLine::query()->create([
            'bom_id' => $bom->id,
            'component_item_id' => $component->id,
            'qty' => '2.0000',
            'unit_id' => $component->unit_id,
            'scrap_percent' => '10.0000',
        ]);
        $stock = StockLevel::query()->create([
            'item_id' => $component->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => '10.0000',
            'qty_reserved' => '0.0000',
            'qty_available' => '10.0000',
        ]);

        $this->post(route('work-orders.store'), [
            'sales_order_line_id' => $salesOrderLine->id,
            'bom_id' => $bom->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '3.00',
        ])->assertRedirect();

        $workOrder = WorkOrder::query()->firstOrFail();
        $this->get(route('work-orders.show', $workOrder))->assertOk();
        $material = $workOrder->materials()->firstOrFail();
        $this->assertSame('6.6000', $material->qty_required);

        $this->post(route('work-orders.materials.issue', [$workOrder, $material]), [
            'qty' => '6.60',
            'issue_date' => '2026-10-22',
        ])->assertRedirect();

        $this->assertSame('3.4000', $stock->fresh()->qty_available);
        $this->assertSame('released', $workOrder->fresh()->status);
        $this->assertDatabaseCount('general_journal_entries', 0);

        $this->post(route('work-orders.production-entries.store', $workOrder), [
            'production_date' => '2026-10-23',
            'qty' => '3.00',
            'cost' => '125.50',
        ])->assertRedirect();

        $entry = $workOrder->productionEntries()->firstOrFail();
        $this->assertSame('completed', $workOrder->fresh()->status);

        $this->post(route('work-orders.inspections.store', [$workOrder, $entry]), [
            'inspection_date' => '2026-10-24',
            'qty_pass' => '2.00',
            'qty_reject' => '1.00',
        ])->assertRedirect();

        $inspection = $workOrder->qcInspections()->firstOrFail();
        $this->assertSame('partial', $inspection->result);

        $this->post(route('work-orders.receipts.store', [$workOrder, $inspection]), [
            'receipt_date' => '2026-10-25',
            'qty' => '2.00',
        ])->assertRedirect();

        $finishedStock = StockLevel::query()
            ->where('item_id', $workOrder->item_id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();
        $this->assertSame('2.0000', $finishedStock->qty_on_hand);
        $this->assertDatabaseCount('inventory_transactions', 2);
        $this->assertDatabaseHas('general_journal_entries', [
            'source_type' => FinishedGoodsReceipt::class,
        ]);
        $this->get(route('work-orders.show', $workOrder))->assertOk();
    }

    public function test_make_to_stock_requires_an_active_finished_item_and_matching_active_bom(): void
    {
        $rawItem = Item::factory()->create(['item_type' => 'raw']);
        $finishedItem = Item::factory()->create(['item_type' => 'finished']);
        $bom = Bom::query()->create([
            'item_id' => $finishedItem->id,
            'version' => '1',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-MTS',
            'name' => 'Gudang Persediaan',
            'is_active' => true,
        ]);

        $this->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'production_type' => 'make_to_stock',
                'item_id' => $rawItem->id,
                'bom_id' => $bom->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => '2',
            ])
            ->assertSessionHasErrors('item_id');

        $this->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'production_type' => 'make_to_stock',
                'item_id' => $finishedItem->id,
                'bom_id' => Bom::query()->create([
                    'item_id' => $rawItem->id,
                    'version' => '1',
                    'is_active' => true,
                ])->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => '2',
            ])
            ->assertSessionHasErrors('bom_id');

        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_work_order_requires_confirmed_sales_order_matching_active_bom_and_available_quantity(): void
    {
        [$salesOrderLine, $bom, $warehouse] = $this->createProductionSetup('draft');

        $this->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'sales_order_line_id' => $salesOrderLine->id,
                'bom_id' => $bom->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => '11',
            ])
            ->assertSessionHasErrors('sales_order_line_id');
        $this->assertDatabaseCount('work_orders', 0);

        $salesOrderLine->salesOrder->update(['status' => 'confirmed']);
        $otherItem = Item::factory()->create();
        $otherBom = Bom::query()->create([
            'item_id' => $otherItem->id,
            'version' => '1',
            'is_active' => true,
        ]);

        $this->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'sales_order_line_id' => $salesOrderLine->id,
                'bom_id' => $otherBom->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => '11',
            ])
            ->assertSessionHasErrors('bom_id');
        $this->assertDatabaseCount('work_orders', 0);

        $this->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'sales_order_line_id' => $salesOrderLine->id,
                'bom_id' => $bom->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => '6',
            ])
            ->assertRedirect();
        $this->assertDatabaseCount('work_orders', 1);

        $this->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'sales_order_line_id' => $salesOrderLine->id,
                'bom_id' => $bom->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => '5',
            ])
            ->assertSessionHasErrors('qty_planned');
        $this->assertDatabaseCount('work_orders', 1);
    }

    /**
     * @return array{SalesOrderLine, Bom, Warehouse}
     */
    private function createProductionSetup(string $status = 'confirmed'): array
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create(['item_type' => 'finished']);
        $salesOrder = SalesOrder::query()->create([
            'so_number' => 'SO261000999',
            'customer_id' => $customer->id,
            'order_date' => '2026-10-17',
            'status' => $status,
            'total_amount' => '100.00',
        ]);
        $salesOrderLine = SalesOrderLine::query()->create([
            'sales_order_id' => $salesOrder->id,
            'item_id' => $item->id,
            'qty' => '10.0000',
            'unit_price' => '10.0000',
            'unit_id' => $item->unit_id,
        ]);
        $bom = Bom::query()->create([
            'item_id' => $item->id,
            'version' => '1',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-TEST',
            'name' => 'Gudang Produksi',
            'is_active' => true,
        ]);

        return [$salesOrderLine, $bom, $warehouse];
    }
}
