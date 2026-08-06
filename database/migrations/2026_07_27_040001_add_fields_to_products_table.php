<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add business fields to the products table:
     * - sku: optional SKU from the brand or marketplace
     * - description: product description
     * - status: Active / Inactive
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('code');
            $table->text('description')->nullable()->after('sku');
            $table->string('status', 50)->default('Active')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'description', 'status']);
        });
    }
};
