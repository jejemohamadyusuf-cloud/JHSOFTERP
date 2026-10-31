<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'sales_orders',
            'purchase_orders',
            'deliveries',
            'goods_receipts',
            'invoices',
            'payments',
            'sales_returns',
            'purchase_returns',
            'stock_adjustments',
            'stock_takes',
            'work_orders',
            'general_journal_entries',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        Schema::create('transaction_cancellations', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_type', 40);
            $table->unsignedBigInteger('transaction_id');
            $table->text('reason');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['transaction_type', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_cancellations');

        foreach ([
            'general_journal_entries',
            'work_orders',
            'stock_takes',
            'stock_adjustments',
            'purchase_returns',
            'sales_returns',
            'payments',
            'invoices',
            'goods_receipts',
            'deliveries',
            'purchase_orders',
            'sales_orders',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
