<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique(); // ISO 3166-1 alpha-2
            $table->string('name');
            $table->string('currency_code', 3);
            $table->string('currency_symbol', 10);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->string('shipping_zone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('phone_code', 10)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 10)->default('H:i:s');
            $table->string('decimal_separator', 1)->default('.');
            $table->string('thousands_separator', 1)->default(',');
            $table->boolean('tax_inclusive')->default(false);
            $table->boolean('requires_vat_number')->default(false);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_order_amount', 10, 2)->nullable();
            $table->json('supported_payment_methods')->nullable();
            $table->json('shipping_carriers')->nullable();
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
