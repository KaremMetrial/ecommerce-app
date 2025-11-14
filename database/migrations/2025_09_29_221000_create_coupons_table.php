<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['fixed', 'percentage']); // Fixed amount or percentage
            $table->decimal('value', 10, 2); // Discount amount or percentage
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Minimum order amount to use coupon
            $table->integer('usage_limit')->nullable(); // Maximum times coupon can be used
            $table->integer('usage_limit_per_user')->nullable(); // Maximum times per user
            $table->integer('used_count')->default(0); // How many times it has been used
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('applicable_products')->nullable(); // Product IDs this coupon applies to
            $table->json('applicable_categories')->nullable(); // Category IDs this coupon applies to
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
