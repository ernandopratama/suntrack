<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add business fields to the variants table:
     * - sku: marketplace SKU
     * - status: Active / Inactive
     * Rename stock -> current_stock for clarity.
     */
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('code');
            $table->string('status', 50)->default('Active')->after('stock');
            $table->renameColumn('stock', 'current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->renameColumn('current_stock', 'stock');
            $table->dropColumn(['sku', 'status']);
        });
    }
};
