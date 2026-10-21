<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_openings', function (Blueprint $table): void {
            $table->id();
            $table->string('opening_number', 40)->unique();
            $table->date('opening_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opening_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opening_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opening_lines');
        Schema::dropIfExists('stock_openings');
    }
};
