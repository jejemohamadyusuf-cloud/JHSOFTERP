<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_prices', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->after('item_id')->constrained()->restrictOnDelete();
            $table->index(
                ['supplier_id', 'item_id', 'price_type', 'start_date', 'end_date'],
                'master_prices_supplier_item_period_index',
            );
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('standard_cost');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->decimal('standard_cost', 18, 4)->default(0);
        });

        Schema::table('master_prices', function (Blueprint $table): void {
            $table->dropIndex('master_prices_supplier_item_period_index');
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
