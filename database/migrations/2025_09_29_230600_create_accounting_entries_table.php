<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description');
            $table->enum('type', ['debit', 'credit']);
            $table->string('category'); // sales, purchases, expenses, tax, shipping, discounts, refunds, fees
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 12, 8)->default(1.00000000);
            $table->string('reference_type')->nullable(); // Polymorphic reference
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('account_code'); // Chart of accounts code
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['date', 'type']);
            $table->index(['category', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('reconciled');
            $table->index('account_code');

            $table->foreign('currency')->references('code')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
