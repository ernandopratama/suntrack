<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\PerformanceReport;
use App\Models\SecureLink;
use App\Models\Task;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Brand $brand;

    private Brand $outsideBrand;

    private User $admin;

    private User $team;

    private User $otherTeam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::create(['name' => 'Workflow Company']);
        $outsideCompany = Company::create(['name' => 'Outside Company']);
        $this->brand = Brand::create(['company_id' => $this->company->id, 'name' => 'Workflow Brand']);
        $this->outsideBrand = Brand::create(['company_id' => $outsideCompany->id, 'name' => 'Outside Brand']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RbacRegistry::ADMIN);

        $this->team = User::factory()->create();
        $this->team->assignRole(RbacRegistry::TEAM);
        $this->team->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $this->team->assignedBrands()->attach($this->brand->id, ['assigned_by' => $this->admin->id]);

        $this->otherTeam = User::factory()->create();
        $this->otherTeam->assignRole(RbacRegistry::TEAM);
        $this->otherTeam->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $this->otherTeam->assignedBrands()->attach($this->brand->id, ['assigned_by' => $this->admin->id]);
    }

    public function test_campaign_ownership_and_lifecycle_are_guarded_and_audited(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/campaigns', [
            'brand_id' => $this->brand->id,
            'name' => 'Enterprise Campaign',
            'objective' => 'Increase qualified leads',
            'priority' => 'urgent',
            'pic_id' => $this->admin->id,
            'member_ids' => [$this->team->id],
            'deadline' => now()->addWeek()->toISOString(),
            'status' => 'Running',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.campaign.status', 'draft')
            ->assertJsonPath('data.campaign.created_by', $this->admin->id)
            ->assertJsonPath('data.campaign.members.0.id', $this->team->id);

        $campaign = Campaign::where('name', 'Enterprise Campaign')->firstOrFail();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'assigned'])
            ->assertOk()
            ->assertJsonPath('data.campaign.status', 'assigned');

        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'in_progress'])
            ->assertOk();

        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'waiting_review'])
            ->assertOk();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'revision'])
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", [
                'status' => 'revision',
                'note' => 'Adjust the final visual.',
            ])->assertOk();

        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'in_progress'])
            ->assertOk();
        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'waiting_review'])
            ->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'approved'])
            ->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/transition", ['status' => 'completed'])
            ->assertOk();

        $this->assertNotNull($campaign->fresh()->completed_at);
        $this->assertGreaterThanOrEqual(8, ActivityLog::whereMorphedTo('loggable', $campaign)->count());
    }

    public function test_campaign_rejects_team_member_outside_brand_scope(): void
    {
        $outsideTeam = User::factory()->create();
        $outsideTeam->assignRole(RbacRegistry::TEAM);
        $outsideTeam->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $outsideTeam->assignedBrands()->attach($this->outsideBrand->id, ['assigned_by' => $this->admin->id]);

        $this->actingAs($this->admin)->postJson('/api/v1/admin/campaigns', [
            'brand_id' => $this->brand->id,
            'name' => 'Invalid Assignment',
            'pic_id' => $this->admin->id,
            'member_ids' => [$outsideTeam->id],
            'deadline' => now()->addWeek()->toISOString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('member_ids');

        $this->assertDatabaseMissing('campaigns', ['name' => 'Invalid Assignment']);
    }

    public function test_standalone_task_lifecycle_is_limited_to_assignee_and_reviewer(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/tasks', [
            'brand_id' => $this->brand->id,
            'name' => 'Standalone Task',
            'description' => 'Prepare the approved asset.',
            'priority' => 'mid',
            'pic_id' => $this->admin->id,
            'assignee_id' => $this->team->id,
            'deadline' => now()->addDays(2)->toISOString(),
            'progress_status' => 'Completed',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.task.progress_status', 'pending')
            ->assertJsonPath('data.task.campaign_id', null);

        $task = Task::where('name', 'Standalone Task')->firstOrFail();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'assigned'])
            ->assertOk();

        $this->actingAs($this->otherTeam)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'in_progress'])
            ->assertForbidden();

        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'in_progress'])
            ->assertOk();
        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'on_hold'])
            ->assertUnprocessable();
        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", [
                'status' => 'on_hold',
                'note' => 'Waiting for source material.',
            ])->assertOk();
        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'in_progress'])
            ->assertOk();

        $this->actingAs($this->team)->putJson("/api/v1/admin/tasks/{$task->id}", [
            'completion_summary' => 'Asset prepared and checked.',
            'completion_details' => 'Final asset is ready for review.',
        ])->assertOk();

        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'waiting_review'])
            ->assertOk();
        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'completed'])
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/tasks/{$task->id}/transition", ['status' => 'completed'])
            ->assertOk();

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_performance_report_scope_review_publish_and_versioning(): void
    {
        $response = $this->actingAs($this->team)->postJson('/api/v1/admin/performance-reports', [
            'brand_id' => $this->brand->id,
            'pic_id' => $this->admin->id,
            'report_type' => 'daily',
            'title' => 'Daily Performance',
            'period_start' => now()->toDateString(),
            'period_end' => now()->toDateString(),
            'executive_summary' => 'Daily activity completed.',
            'content' => '<p>Campaign assets and monitoring were completed.</p>',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.report.status', 'draft')
            ->assertJsonPath('data.report.author_id', $this->team->id);

        $report = PerformanceReport::where('title', 'Daily Performance')->firstOrFail();

        $outsideTeam = User::factory()->create();
        $outsideTeam->assignRole(RbacRegistry::TEAM);
        $outsideTeam->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $outsideTeam->assignedBrands()->attach($this->outsideBrand->id, ['assigned_by' => $this->admin->id]);
        $this->actingAs($outsideTeam)
            ->getJson("/api/v1/admin/performance-reports/{$report->id}")
            ->assertForbidden();

        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/transition", ['status' => 'waiting_review'])
            ->assertOk();
        $this->actingAs($this->team)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/transition", ['status' => 'approved'])
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/transition", ['status' => 'approved'])
            ->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/transition", ['status' => 'published'])
            ->assertOk();

        $this->actingAs($this->team)
            ->putJson("/api/v1/admin/performance-reports/{$report->id}", ['title' => 'Changed Published Report'])
            ->assertUnprocessable();

        $versionResponse = $this->actingAs($this->team)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/versions")
            ->assertCreated()
            ->assertJsonPath('data.report.status', 'draft')
            ->assertJsonPath('data.report.version', 2)
            ->assertJsonPath('data.report.supersedes_report_id', $report->id);

        $this->assertDatabaseHas('performance_reports', [
            'id' => $versionResponse->json('data.report.id'),
            'author_id' => $this->team->id,
        ]);
    }

    public function test_public_secure_link_cannot_bypass_internal_campaign_or_task_lifecycle(): void
    {
        $campaign = Campaign::create([
            'brand_id' => $this->brand->id,
            'created_by' => $this->admin->id,
            'pic_id' => $this->admin->id,
            'name' => 'Protected Campaign',
            'status' => 'in_progress',
        ]);
        $task = Task::create([
            'brand_id' => $this->brand->id,
            'campaign_id' => $campaign->id,
            'created_by' => $this->admin->id,
            'name' => 'Protected Task',
            'progress_status' => 'in_progress',
        ]);
        $link = SecureLink::create([
            'linkable_type' => Campaign::class,
            'linkable_id' => $campaign->id,
            'token' => Str::random(64),
            'created_by' => $this->admin->id,
        ]);

        $this->patchJson("/api/v1/public/reviews/{$link->token}/status", ['status' => 'Completed'])
            ->assertForbidden();
        $this->postJson("/api/v1/public/review/{$link->token}/tasks/{$task->id}/progress", [
            'progress_status' => 'completed',
        ])->assertForbidden();

        $this->assertSame('in_progress', $campaign->fresh()->status);
        $this->assertSame('in_progress', $task->fresh()->progress_status);
    }
}
