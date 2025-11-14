<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('calculation_type'); // flat_rate, per_item, per_weight, percentage
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('cost_per_item', 10, 2)->nullable();
            $table->decimal('cost_per_weight', 10, 2)->nullable(); // per kg/lb
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->string('delivery_time')->nullable();
            $table->decimal('max_weight', 8, 2)->nullable();
            $table->json('max_dimensions')->nullable(); // length, width, height
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('carrier')->nullable(); // ups, fedex, dhl, etc.
            $table->string('carrier_service')->nullable();
            $table->boolean('tracking_available')->default(false);
            $table->boolean('insurance_available')->default(false);
            $table->boolean('signature_required')->default(false);
            $table->timestamps();

            $table->index(['shipping_zone_id', 'is_active']);
            $table->index('code');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
