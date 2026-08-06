<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add bottom_price snapshot and notes to promotion_variant pivot.
     *
     * bottom_price here stores the master bottom price AT THE TIME the
     * variant was added to the promotion — preserving pricing history.
     * notes stores admin / rejection notes per variant entry.
     */
    public function up(): void
    {
        Schema::table('promotion_variant', function (Blueprint $table) {
            $table->decimal('bottom_price', 12, 2)->default(0)->after('campaign_price');
            $table->decimal('normal_price_snapshot', 12, 2)->default(0)->after('bottom_price');
            $table->text('notes')->nullable()->after('rejection_notes');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_variant', function (Blueprint $table) {
            $table->dropColumn(['bottom_price', 'normal_price_snapshot', 'notes']);
        });
    }
};
