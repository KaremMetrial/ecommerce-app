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
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('address_name')->nullable()->after('is_default');
            $table->text('notes')->nullable()->after('address_name');
            $table->json('coordinates')->nullable()->after('notes');
            $table->json('metadata')->nullable()->after('coordinates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['address_name', 'notes', 'coordinates', 'metadata']);
        });
    }
};
