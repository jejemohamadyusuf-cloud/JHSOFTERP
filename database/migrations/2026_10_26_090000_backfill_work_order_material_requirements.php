<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $lines = DB::table('work_orders')
            ->join('bom_lines', 'bom_lines.bom_id', '=', 'work_orders.bom_id')
            ->whereNotIn('work_orders.status', ['completed', 'cancelled'])
            ->select([
                'work_orders.id as work_order_id',
                'work_orders.warehouse_id',
                'work_orders.qty_planned',
                'bom_lines.component_item_id',
                'bom_lines.qty',
                'bom_lines.scrap_percent',
            ])
            ->get();

        foreach ($lines as $line) {
            $requiredQty = (float) $line->qty_planned
                * (float) $line->qty
                * (1 + (float) $line->scrap_percent / 100);
            $requiredQty = ceil($requiredQty * 100 - 0.000001) / 100;

            DB::table('work_order_materials')->insert([
                'work_order_id' => $line->work_order_id,
                'item_id' => $line->component_item_id,
                'warehouse_id' => $line->warehouse_id,
                'qty_required' => number_format($requiredQty, 4, '.', ''),
                'qty_issued' => '0',
                'unit_cost' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
