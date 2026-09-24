<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('promotions')
            ->whereNull('campaign_id')
            ->orderBy('id')
            ->each(function (object $promotion): void {
                $campaignId = (string) Str::uuid();

                DB::table('campaigns')->insert([
                    'id' => $campaignId,
                    'brand_id' => $promotion->brand_id,
                    'name' => 'Kampanye Migrasi - '.$promotion->name,
                    'description' => 'Dibuat otomatis saat promosi diwajibkan memiliki kampanye.',
                    'start_date' => $promotion->start_date,
                    'end_date' => $promotion->end_date,
                    'status' => 'draft',
                    'created_at' => $promotion->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                DB::table('promotions')->where('id', $promotion->id)->update([
                    'campaign_id' => $campaignId,
                    'updated_at' => now(),
                ]);
            });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->uuid('campaign_id')->nullable(false)->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->uuid('campaign_id')->nullable()->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->restrictOnDelete();
        });
    }
};
