<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->string('adjustment_number', 40)->unique();
            $table->string('direction', 3);
            $table->date('adjustment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('direction');
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->timestamps();
        });

        if (! Schema::hasTable('stock_openings')) {
            return;
        }

        $openings = DB::table('stock_openings')->orderBy('id')->get();

        foreach ($openings as $opening) {
            DB::table('stock_adjustments')->insert([
                'id' => $opening->id,
                'adjustment_number' => preg_replace('/^OP/', 'AI', $opening->opening_number),
                'direction' => 'in',
                'adjustment_date' => $opening->opening_date,
                'notes' => $opening->notes,
                'created_at' => $opening->created_at,
                'updated_at' => $opening->updated_at,
            ]);

            $lines = DB::table('stock_opening_lines')
                ->where('stock_opening_id', $opening->id)
                ->orderBy('id')
                ->get();

            foreach ($lines as $line) {
                DB::table('stock_adjustment_lines')->insert([
                    'id' => $line->id,
                    'stock_adjustment_id' => $opening->id,
                    'item_id' => $line->item_id,
                    'warehouse_id' => $line->warehouse_id,
                    'location_id' => $line->location_id,
                    'qty' => $line->qty,
                    'unit_cost' => $line->unit_cost,
                    'created_at' => $line->created_at,
                    'updated_at' => $line->updated_at,
                ]);
            }
        }

        DB::table('inventory_transactions')
            ->where('reference_type', 'App\\Models\\StockOpening')
            ->update(['reference_type' => 'App\\Models\\StockAdjustment']);

        DB::table('transaction_sequences')
            ->where('sequence_key', 'like', 'OP%')
            ->update(['sequence_key' => DB::raw("replace(sequence_key, 'OP', 'AI')")]);
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_openings')) {
            $hasOutAdjustments = DB::table('stock_adjustments')->where('direction', 'out')->exists();
            if ($hasOutAdjustments) {
                throw new RuntimeException('Stock adjustment out documents exist and cannot be migrated back to stock openings.');
            }

            $adjustments = DB::table('stock_adjustments')->orderBy('id')->get();
            foreach ($adjustments as $adjustment) {
                DB::table('stock_openings')->updateOrInsert(
                    ['id' => $adjustment->id],
                    [
                        'opening_number' => preg_replace('/^AI/', 'OP', $adjustment->adjustment_number),
                        'opening_date' => $adjustment->adjustment_date,
                        'notes' => $adjustment->notes,
                        'created_at' => $adjustment->created_at,
                        'updated_at' => $adjustment->updated_at,
                    ],
                );

                $lines = DB::table('stock_adjustment_lines')
                    ->where('stock_adjustment_id', $adjustment->id)
                    ->orderBy('id')
                    ->get();

                foreach ($lines as $line) {
                    DB::table('stock_opening_lines')->updateOrInsert(
                        ['id' => $line->id],
                        [
                            'stock_opening_id' => $adjustment->id,
                            'item_id' => $line->item_id,
                            'warehouse_id' => $line->warehouse_id,
                            'location_id' => $line->location_id,
                            'qty' => $line->qty,
                            'unit_cost' => $line->unit_cost,
                            'created_at' => $line->created_at,
                            'updated_at' => $line->updated_at,
                        ],
                    );
                }
            }

            DB::table('inventory_transactions')
                ->where('reference_type', 'App\\Models\\StockAdjustment')
                ->update(['reference_type' => 'App\\Models\\StockOpening']);

            DB::table('transaction_sequences')
                ->where('sequence_key', 'like', 'AI%')
                ->update(['sequence_key' => DB::raw("replace(sequence_key, 'AI', 'OP')")]);
        }

        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
    }
};
