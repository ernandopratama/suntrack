<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CampaignMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-09 10:00:00');
        $this->seed(RolePermissionSeeder::class);

        $company = Company::create(['name' => 'Campaign Monitoring Company']);
        $this->brand = Brand::create([
            'company_id' => $company->id,
            'name' => 'Campaign Monitoring Brand',
        ]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(RbacRegistry::ADMIN);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_campaign_monitoring_filters_and_deadline_indicators_match_prd(): void
    {
        app(SettingsService::class)->set(
            'campaign_approaching_deadline_days',
            7,
            'integer',
            'workflow'
        );

        $this->campaign('Active Far', 'in_progress', now()->addDays(10));
        $this->campaign('Approaching', 'assigned', now()->addDays(3));
        $this->campaign('Overdue', 'in_progress', now()->subHour());
        $this->campaign('Waiting Review', 'waiting_review', now()->addDays(10));
        $this->campaign('Revision', 'revision', now()->addDays(10));
        $this->campaign('Completed', 'completed', now()->subDay());
        $this->campaign('Cancelled', 'cancelled', now()->subDay());

        $this->assertCampaignNames('active', [
            'Active Far',
            'Approaching',
            'Overdue',
            'Waiting Review',
            'Revision',
        ]);
        $this->assertCampaignNames('completed', ['Completed']);
        $this->assertCampaignNames('approaching_deadline', ['Approaching']);
        $this->assertCampaignNames('overdue', ['Overdue']);
        $this->assertCampaignNames('waiting_review', ['Waiting Review']);
        $this->assertCampaignNames('revision', ['Revision']);

        $campaigns = collect($this->actingAs($this->admin)
            ->getJson('/api/v1/admin/campaigns?per_page=100')
            ->assertOk()
            ->json('data.campaigns.data'))
            ->keyBy('name');

        $this->assertSame('approaching_deadline', $campaigns['Approaching']['deadline_state']);
        $this->assertSame('overdue', $campaigns['Overdue']['deadline_state']);
        $this->assertNull($campaigns['Completed']['deadline_state']);
        $this->assertSame('Campaign Monitoring Brand', $campaigns['Approaching']['brand']['name']);
    }

    public function test_campaign_approaching_interval_uses_system_setting(): void
    {
        $this->campaign('Three Days Away', 'assigned', now()->addDays(3));
        $settings = app(SettingsService::class);

        $settings->set('campaign_approaching_deadline_days', 2, 'integer', 'workflow');
        $this->assertCampaignNames('approaching_deadline', []);

        $settings->set('campaign_approaching_deadline_days', 5, 'integer', 'workflow');
        $this->assertCampaignNames('approaching_deadline', ['Three Days Away']);
    }

    public function test_campaign_filters_sorting_and_validation_are_supported(): void
    {
        $this->campaign('Zulu Urgent', 'draft', null, 'urgent');
        $this->campaign('Alpha Normal', 'draft', null, 'normal');

        $campaigns = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/campaigns?priority=urgent&sort_by=name&sort_direction=asc')
            ->assertOk()
            ->json('data.campaigns.data');

        $this->assertSame(['Zulu Urgent'], collect($campaigns)->pluck('name')->all());

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/campaigns?monitoring=unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('monitoring');

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/campaigns?sort_by=deleted_at')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_by');
    }

    public function test_campaign_review_requires_evidence_and_completion_confirmation(): void
    {
        $campaign = $this->campaign('Lifecycle Guard', 'in_progress', now()->addDays(3));

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", [
                'status' => 'waiting_review',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments');

        Attachment::create([
            'attachable_type' => Campaign::class,
            'attachable_id' => $campaign->id,
            'uploaded_by' => $this->admin->id,
            'disk' => 'local',
            'path' => 'attachments/campaign/result.pdf',
            'original_name' => 'result.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", [
                'status' => 'waiting_review',
            ])->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", [
                'status' => 'approved',
                'note' => 'Evidence approved.',
            ])->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", [
                'status' => 'completed',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('note');
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", [
                'status' => 'completed',
                'note' => 'Client delivery confirmed.',
            ])->assertOk();

        $this->assertNotNull($campaign->fresh()->completed_at);
        $this->assertDatabaseHas('activity_logs', [
            'loggable_type' => Campaign::class,
            'loggable_id' => $campaign->id,
            'action' => 'Status Changed',
        ]);
    }

    public function test_campaign_can_be_deleted_through_crud_endpoint_and_is_audited(): void
    {
        $campaign = $this->campaign('Delete Me', 'draft');

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/campaigns/{$campaign->id}")
            ->assertOk();

        $this->assertSoftDeleted($campaign);
        $this->assertTrue(ActivityLog::query()
            ->where('loggable_type', Campaign::class)
            ->where('loggable_id', $campaign->id)
            ->where('action', 'Deleted')
            ->exists());
    }

    private function campaign(
        string $name,
        string $status,
        ?Carbon $deadline = null,
        string $priority = 'normal'
    ): Campaign {
        return Campaign::create([
            'brand_id' => $this->brand->id,
            'created_by' => $this->admin->id,
            'name' => $name,
            'status' => $status,
            'priority' => $priority,
            'deadline' => $deadline,
        ]);
    }

    /** @param array<int, string> $expected */
    private function assertCampaignNames(string $monitoring, array $expected): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/campaigns?monitoring={$monitoring}&per_page=100")
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            $expected,
            collect($response->json('data.campaigns.data'))->pluck('name')->all()
        );
    }
}
