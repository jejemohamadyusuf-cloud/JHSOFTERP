<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->string('reference_number', 40)->nullable()->after('gr_number');
        });

        $receipts = DB::table('goods_receipts')
            ->join('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->select('goods_receipts.id', 'purchase_orders.po_number')
            ->get();

        foreach ($receipts as $receipt) {
            DB::table('goods_receipts')
                ->where('id', $receipt->id)
                ->update(['reference_number' => $receipt->po_number]);
        }
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropColumn('reference_number');
        });
    }
};
