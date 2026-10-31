<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('journal_number', 40)->unique();
            $table->date('journal_date');
            $table->string('reference', 100)->nullable();
            $table->string('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at');
            $table->timestamps();
            $table->index(['journal_date', 'id']);
        });

        Schema::create('general_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('general_journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit_amount', 18, 2)->default(0);
            $table->decimal('credit_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['chart_of_account_id', 'general_journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_journal_lines');
        Schema::dropIfExists('general_journal_entries');
    }
};
