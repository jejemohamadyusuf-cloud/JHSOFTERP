<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table): void {
            $table->foreignId('master_price_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('master_prices')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('master_price_id');
        });
    }
};
