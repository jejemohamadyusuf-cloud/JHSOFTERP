<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        DB::table('pic_guides')->insert([
            [
                'module' => 'purchase-orders',
                'department' => 'Purchasing / Pengadaan',
                'input_role' => 'Purchasing Admin atau Buyer',
                'review_role' => 'Purchasing Manager',
                'is_enabled' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'module' => 'stock',
                'department' => 'Gudang / Logistik',
                'input_role' => 'Warehouse Admin atau Inventory Controller',
                'review_role' => 'Warehouse Manager',
                'is_enabled' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('pic_guides')->whereIn('module', ['purchase-orders', 'stock'])->delete();
    }
};
