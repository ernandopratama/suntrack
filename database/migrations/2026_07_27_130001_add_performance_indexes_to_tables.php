<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add composite and single-column indexes for high-frequency queries and dashboard aggregations (Sprint 10).
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['brand_id', 'status'], 'camp_brand_status_idx');
            $table->index(['brand_id', 'end_date'], 'camp_brand_end_idx');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->index(['campaign_id', 'status'], 'prom_camp_status_idx');
            $table->index(['status', 'end_date'], 'prom_status_end_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['brand_id', 'status'], 'prod_brand_status_idx');
            $table->index('sku', 'prod_sku_idx');
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->index(['product_id', 'sku'], 'var_prod_sku_idx');
        });

        Schema::table('secure_links', function (Blueprint $table) {
            $table->index(['revoked_at', 'expires_at'], 'sec_revoked_exp_idx');
        });

        Schema::table('approval_histories', function (Blueprint $table) {
            $table->index('new_status', 'appr_new_status_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at', 'act_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('camp_brand_status_idx');
            $table->dropIndex('camp_brand_end_idx');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropIndex('prom_camp_status_idx');
            $table->dropIndex('prom_status_end_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('prod_brand_status_idx');
            $table->dropIndex('prod_sku_idx');
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropIndex('var_prod_sku_idx');
        });

        Schema::table('secure_links', function (Blueprint $table) {
            $table->dropIndex('sec_revoked_exp_idx');
        });

        Schema::table('approval_histories', function (Blueprint $table) {
            $table->dropIndex('appr_new_status_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('act_created_at_idx');
        });
    }
};
