<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'promotions_campaign_active_slot_unique';

    public function up(): void
    {
        // Replace the generated column used by the first implementation when
        // upgrading an installation on which that migration already succeeded.
        if (Schema::hasColumn('promotions', 'active_campaign_id')) {
            Schema::table('promotions', function (Blueprint $table) {
                $table->dropUnique('promotions_active_campaign_unique');
            });

            Schema::table('promotions', function (Blueprint $table) {
                $table->dropColumn('active_campaign_id');
            });
        }

        if (! Schema::hasColumn('promotions', 'active_slot')) {
            Schema::table('promotions', function (Blueprint $table) {
                $table->unsignedTinyInteger('active_slot')
                    ->nullable()
                    ->default(1)
                    ->after('campaign_id');
            });
        }

        DB::table('promotions')
            ->whereNull('deleted_at')
            ->update(['active_slot' => 1]);

        DB::table('promotions')
            ->whereNotNull('deleted_at')
            ->update(['active_slot' => null]);

        if (! Schema::hasIndex('promotions', self::UNIQUE_INDEX)) {
            Schema::table('promotions', function (Blueprint $table) {
                $table->unique(['campaign_id', 'active_slot'], self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('promotions', self::UNIQUE_INDEX)) {
            Schema::table('promotions', function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }

        if (Schema::hasColumn('promotions', 'active_slot')) {
            Schema::table('promotions', function (Blueprint $table) {
                $table->dropColumn('active_slot');
            });
        }
    }
};
