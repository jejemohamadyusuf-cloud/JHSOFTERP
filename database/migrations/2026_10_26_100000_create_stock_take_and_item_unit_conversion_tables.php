<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_unit_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('base_units');
            $table->timestamps();
            $table->unique(['item_id', 'unit_id']);
            $table->index(['item_id', 'base_units']);
        });

        Schema::create('stock_takes', function (Blueprint $table): void {
            $table->id();
            $table->string('stock_take_number', 40)->unique();
            $table->date('stock_take_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_take_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('system_qty', 18, 4);
            $table->decimal('physical_qty', 18, 4);
            $table->decimal('discrepancy_qty', 18, 4);
            $table->timestamps();
            $table->unique(['stock_take_id', 'item_id', 'warehouse_id', 'location_id'], 'stock_take_line_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_lines');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('item_unit_conversions');
    }
};
