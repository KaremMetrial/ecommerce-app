<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->onDelete('cascade');
            $table->string('country_code', 2);
            $table->string('state')->nullable();
            $table->string('postal_code_from')->nullable();
            $table->string('postal_code_to')->nullable();
            $table->decimal('weight_from', 8, 2)->nullable();
            $table->decimal('weight_to', 8, 2)->nullable();
            $table->decimal('subtotal_from', 10, 2)->nullable();
            $table->decimal('subtotal_to', 10, 2)->nullable();
            $table->decimal('cost', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shipping_method_id', 'is_active']);
            $table->index(['country_code', 'state']);
            $table->index(['weight_from', 'weight_to']);
            $table->index(['subtotal_from', 'subtotal_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
