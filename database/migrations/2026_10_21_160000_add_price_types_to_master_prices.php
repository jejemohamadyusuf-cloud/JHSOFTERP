<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_prices', function (Blueprint $table): void {
            $table->string('price_type', 10)->default('sell')->after('item_id');
            $table->index(['item_id', 'price_type', 'start_date', 'end_date'], 'master_prices_item_type_period_index');
        });
    }

    public function down(): void
    {
        Schema::table('master_prices', function (Blueprint $table): void {
            $table->dropIndex('master_prices_item_type_period_index');
            $table->dropColumn('price_type');
        });
    }
};
