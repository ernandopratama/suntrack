<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEY = 'campaign_approaching_deadline_days';

    public function up(): void
    {
        if (! Schema::hasIndex('campaigns', 'campaign_status_deadline_idx')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->index(['status', 'deadline'], 'campaign_status_deadline_idx');
            });
        }

        DB::table('system_settings')->insertOrIgnore([
            'key' => self::KEY,
            'value' => '7',
            'type' => 'integer',
            'group' => 'workflow',
            'description' => 'Jumlah hari sebelum deadline Campaign ditandai mendekati deadline.',
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasIndex('campaigns', 'campaign_status_deadline_idx')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->dropIndex('campaign_status_deadline_idx');
            });
        }

        DB::table('system_settings')
            ->where('key', self::KEY)
            ->where('value', '7')
            ->delete();
    }
};
