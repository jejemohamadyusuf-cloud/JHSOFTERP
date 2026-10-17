<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 18, 4);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_prices');
    }
};
