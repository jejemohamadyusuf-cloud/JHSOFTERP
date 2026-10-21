<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pic_guides', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 40)->unique();
            $table->string('department', 120);
            $table->string('input_role', 120);
            $table->string('review_role', 120);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        $timestamp = now();
        DB::table('pic_guides')->insert([
            ['module' => 'items', 'department' => 'Master Data / Inventory Control', 'input_role' => 'Master Data Admin atau Inventory Control Staff', 'review_role' => 'Inventory Control Supervisor; Engineering untuk spesifikasi produk', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'item-categories', 'department' => 'Master Data / Inventory Control', 'input_role' => 'Master Data Admin', 'review_role' => 'Inventory Control Supervisor', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'units', 'department' => 'Master Data / Inventory Control', 'input_role' => 'Master Data Admin atau Inventory Control Staff', 'review_role' => 'Inventory Control Supervisor', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'customers', 'department' => 'Penjualan / Sales', 'input_role' => 'Sales Admin atau Customer Service', 'review_role' => 'Sales Supervisor atau Sales Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'suppliers', 'department' => 'Purchasing / Pengadaan', 'input_role' => 'Purchasing Admin atau Buyer', 'review_role' => 'Purchasing Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'warehouses', 'department' => 'Gudang / Logistik', 'input_role' => 'Warehouse Admin atau Inventory Controller', 'review_role' => 'Warehouse Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'locations', 'department' => 'Gudang / Logistik', 'input_role' => 'Warehouse Admin atau Inventory Controller', 'review_role' => 'Warehouse Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'master-prices', 'department' => 'Keuangan / Komersial', 'input_role' => 'Pricing Analyst atau Finance Staff', 'review_role' => 'Finance Manager atau pejabat pricing yang ditunjuk', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'boms', 'department' => 'Engineering / Produksi', 'input_role' => 'Product Engineer atau Process Engineer', 'review_role' => 'Engineering Manager atau Production Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'sales-orders', 'department' => 'Penjualan / Sales', 'input_role' => 'Sales Admin atau Sales Representative', 'review_role' => 'Sales Supervisor atau Sales Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['module' => 'work-orders', 'department' => 'PPIC / Produksi', 'input_role' => 'PPIC Staff atau Production Planner', 'review_role' => 'PPIC Manager atau Production Manager', 'is_enabled' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pic_guides');
    }
};
