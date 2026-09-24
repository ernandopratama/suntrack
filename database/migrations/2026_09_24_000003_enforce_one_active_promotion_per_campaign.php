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
        $duplicateCampaignIds = DB::table('promotions')
            ->whereNull('deleted_at')
            ->groupBy('campaign_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('campaign_id');

        foreach ($duplicateCampaignIds as $campaignId) {
            $promotions = DB::table('promotions')
                ->where('campaign_id', $campaignId)
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($promotions->skip(1) as $promotion) {
                DB::transaction(function () use ($campaignId, $promotion): void {
                    $campaign = DB::table('campaigns')->where('id', $campaignId)->first();

                    if ($campaign === null) {
                        return;
                    }

                    $newCampaignId = (string) Str::uuid();
                    $suffix = ' - '.$promotion->name;

                    DB::table('campaigns')->insert([
                        'id' => $newCampaignId,
                        'brand_id' => $campaign->brand_id,
                        'created_by' => $campaign->created_by,
                        'pic_id' => $campaign->pic_id,
                        'name' => Str::limit($campaign->name.$suffix, 255, ''),
                        'objective' => $campaign->objective,
                        'description' => $campaign->description,
                        'start_date' => $campaign->start_date,
                        'end_date' => $campaign->end_date,
                        'status' => $campaign->status,
                        'priority' => $campaign->priority,
                        'deadline' => $campaign->deadline,
                        'notes' => $campaign->notes,
                        'approval_notes' => $campaign->approval_notes,
                        'completed_at' => $campaign->completed_at,
                        'created_at' => $promotion->created_at ?? now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]);

                    $members = DB::table('campaign_members')
                        ->where('campaign_id', $campaignId)
                        ->get();

                    foreach ($members as $member) {
                        DB::table('campaign_members')->insert([
                            'campaign_id' => $newCampaignId,
                            'user_id' => $member->user_id,
                            'assigned_by' => $member->assigned_by,
                            'created_at' => $member->created_at,
                            'updated_at' => $member->updated_at,
                        ]);
                    }

                    DB::table('promotions')->where('id', $promotion->id)->update([
                        'campaign_id' => $newCampaignId,
                        'updated_at' => now(),
                    ]);
                });
            }
        }

        Schema::table('promotions', function (Blueprint $table) {
            $table->uuid('active_campaign_id')
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN campaign_id ELSE NULL END');
            $table->unique('active_campaign_id', 'promotions_active_campaign_unique');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropUnique('promotions_active_campaign_unique');
            $table->dropColumn('active_campaign_id');
        });
    }
};
