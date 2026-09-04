<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use App\Repositories\DashboardRepository;
use App\Services\Authorization\DataScopeService;
use App\Services\Reporting\ReportingService;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private Brand $brandA1;

    private Brand $brandA2;

    private Brand $brandB1;

    private Brand $brandB2;

    private User $admin;

    private User $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->companyA = Company::create(['name' => 'Company A']);
        $this->companyB = Company::create(['name' => 'Company B']);
        $this->brandA1 = Brand::create(['company_id' => $this->companyA->id, 'name' => 'Brand A1']);
        $this->brandA2 = Brand::create(['company_id' => $this->companyA->id, 'name' => 'Brand A2']);
        $this->brandB1 = Brand::create(['company_id' => $this->companyB->id, 'name' => 'Brand B1']);
        $this->brandB2 = Brand::create(['company_id' => $this->companyB->id, 'name' => 'Brand B2']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RbacRegistry::ADMIN);

        $this->team = User::factory()->create();
        $this->team->assignRole(RbacRegistry::TEAM);
        $this->team->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
    }

    public function test_company_and_brand_assignments_are_combined_without_cross_brand_access(): void
    {
        $this->team->assignedCompanies()->attach($this->companyA->id, ['assigned_by' => $this->admin->id]);
        $this->team->assignedBrands()->attach($this->brandB1->id, ['assigned_by' => $this->admin->id]);

        $scope = app(DataScopeService::class);

        $this->assertEqualsCanonicalizing(
            [$this->companyA->id, $this->companyB->id],
            $scope->effectiveCompanyIds($this->team)->all()
        );
        $this->assertEqualsCanonicalizing(
            [$this->brandA1->id, $this->brandA2->id, $this->brandB1->id],
            $scope->effectiveBrandIds($this->team)->all()
        );
        $this->assertFalse($scope->canAccess($this->team, $this->brandB2));
    }

    public function test_team_list_queries_are_scoped_before_pagination(): void
    {
        $this->team->assignedCompanies()->attach($this->companyA->id, ['assigned_by' => $this->admin->id]);

        $campaignA = Campaign::create([
            'brand_id' => $this->brandA1->id,
            'name' => 'Visible Campaign',
            'status' => 'Draft',
        ]);
        $campaignB = Campaign::create([
            'brand_id' => $this->brandB1->id,
            'name' => 'Hidden Campaign',
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($this->team)
            ->getJson('/api/v1/admin/campaigns?per_page=50');

        $response->assertOk()
            ->assertJsonCount(1, 'data.campaigns.data')
            ->assertJsonPath('data.campaigns.data.0.id', $campaignA->id);

        $this->actingAs($this->team)
            ->getJson("/api/v1/admin/campaigns/{$campaignB->id}")
            ->assertForbidden();
    }

    public function test_team_can_view_assigned_company_but_cannot_create_company_or_brand(): void
    {
        $this->team->assignedCompanies()->attach($this->companyA->id, ['assigned_by' => $this->admin->id]);

        $this->actingAs($this->team)
            ->getJson('/api/v1/admin/companies?per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data.companies.data')
            ->assertJsonPath('data.companies.data.0.id', $this->companyA->id);

        $this->actingAs($this->team)
            ->postJson('/api/v1/admin/companies', ['name' => 'Forbidden Company'])
            ->assertForbidden();

        $this->actingAs($this->team)
            ->postJson('/api/v1/admin/brands', [
                'company_id' => $this->companyA->id,
                'name' => 'Forbidden Brand',
            ])
            ->assertForbidden();
    }

    public function test_admin_has_global_company_access_and_cannot_delete_users(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/companies?per_page=50')
            ->assertOk()
            ->assertJsonCount(2, 'data.companies.data');

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$this->team->id}")
            ->assertForbidden();
    }

    public function test_admin_can_create_team_with_direct_default_permissions_only(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/users', [
            'name' => 'New Team Member',
            'username' => 'new.team',
            'email' => 'new.team@example.test',
            'password' => 'Password123!',
            'company_ids' => [$this->companyA->id],
            'role' => RbacRegistry::TEAM,
        ]);

        $response->assertCreated();

        $created = User::query()->where('username', 'new.team')->firstOrFail();
        $this->assertTrue($created->hasRole(RbacRegistry::TEAM));
        $this->assertEqualsCanonicalizing(
            RbacRegistry::TEAM_DEFAULT_PERMISSIONS,
            $created->permissions->pluck('name')->all()
        );
        $this->assertDatabaseHas('user_company_assignments', [
            'user_id' => $created->id,
            'company_id' => $this->companyA->id,
        ]);

        $this->actingAs($this->admin)->postJson('/api/v1/admin/users', [
            'name' => 'Forbidden Admin',
            'username' => 'forbidden.admin',
            'email' => 'forbidden.admin@example.test',
            'password' => 'Password123!',
            'role' => RbacRegistry::ADMIN,
        ])->assertForbidden();
    }

    public function test_admin_can_update_team_permissions_and_multiple_scope_assignments(): void
    {
        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/users/{$this->team->id}", [
            'name' => $this->team->name,
            'username' => $this->team->username,
            'email' => $this->team->email,
            'permissions' => ['campaign.view', 'activity.view'],
            'company_ids' => [$this->companyA->id],
            'brand_ids' => [$this->brandB1->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.company_ids.0', $this->companyA->id)
            ->assertJsonPath('data.user.brand_ids.0', $this->brandB1->id);

        $this->team->refresh();
        $this->assertEqualsCanonicalizing(
            ['campaign.view', 'activity.view'],
            $this->team->permissions->pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$this->companyA->id, $this->companyB->id],
            app(DataScopeService::class)->effectiveCompanyIds($this->team)->all()
        );
        $this->assertEqualsCanonicalizing(
            [$this->brandA1->id, $this->brandA2->id, $this->brandB1->id],
            app(DataScopeService::class)->effectiveBrandIds($this->team)->all()
        );

        $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$this->team->id}")
            ->assertOk()
            ->assertJsonPath('data.user.effective_scope.global', false)
            ->assertJsonCount(1, 'data.user.access_history');

        $this->actingAs($this->admin)->putJson("/api/v1/admin/users/{$this->team->id}", [
            'name' => $this->team->name,
            'username' => $this->team->username,
            'email' => $this->team->email,
            'permissions' => ['user.view'],
        ])->assertUnprocessable();
    }

    public function test_admin_cannot_edit_itself_and_last_super_admin_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)->putJson("/api/v1/admin/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'username' => $this->admin->username,
            'email' => $this->admin->email,
        ])->assertForbidden();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RbacRegistry::SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/admin/users/{$superAdmin->id}")
            ->assertForbidden();
    }

    public function test_dashboard_activity_and_export_cannot_escape_team_scope(): void
    {
        $this->team->assignedCompanies()->attach($this->companyA->id, ['assigned_by' => $this->admin->id]);

        $campaignA = Campaign::create([
            'brand_id' => $this->brandA1->id,
            'name' => 'Scoped Campaign',
            'status' => 'Running',
        ]);
        $campaignB = Campaign::create([
            'brand_id' => $this->brandB1->id,
            'name' => 'Outside Campaign',
            'status' => 'Running',
        ]);

        foreach ([$campaignA, $campaignB] as $campaign) {
            ActivityLog::create([
                'loggable_type' => Campaign::class,
                'loggable_id' => $campaign->id,
                'action' => 'Created',
                'description' => $campaign->name,
                'actor_type' => 'Admin',
                'actor_id' => $this->admin->id,
                'actor_name' => $this->admin->name,
            ]);
        }

        $dashboard = app(DashboardRepository::class)->getKpiStats(now()->toDateString(), $this->team);
        $this->assertSame(1, $dashboard['campaigns']['total']);

        $activityIds = app(DataScopeService::class)
            ->scopeActivityLogs(ActivityLog::query(), $this->team)
            ->pluck('loggable_id')
            ->all();
        $this->assertSame([$campaignA->id], $activityIds);

        $reporting = app(ReportingService::class);
        $this->assertCount(1, $reporting->generate('campaign', [], $this->team));
        $this->assertSame([], $reporting->generate('campaign', ['brand_id' => $this->brandB1->id], $this->team));
    }

    public function test_activity_log_endpoint_requires_permission_and_applies_team_scope(): void
    {
        $this->team->assignedCompanies()->attach($this->companyA->id, ['assigned_by' => $this->admin->id]);

        $visible = ActivityLog::create([
            'loggable_type' => Brand::class,
            'loggable_id' => $this->brandA1->id,
            'action' => 'Updated',
            'description' => 'Visible activity',
            'actor_type' => 'Admin',
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->name,
        ]);
        $hidden = ActivityLog::create([
            'loggable_type' => Brand::class,
            'loggable_id' => $this->brandB1->id,
            'action' => 'Updated',
            'description' => 'Hidden activity',
            'actor_type' => 'Admin',
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->name,
        ]);

        $this->actingAs($this->team)
            ->getJson('/api/v1/admin/activity-logs?per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data.activity_logs.data')
            ->assertJsonPath('data.activity_logs.data.0.id', $visible->id)
            ->assertJsonMissing(['id' => $hidden->id]);

        $this->team->revokePermissionTo('activity.view');

        $this->actingAs($this->team)
            ->getJson('/api/v1/admin/activity-logs')
            ->assertForbidden();
    }

    public function test_team_cannot_inject_out_of_scope_ids_into_operational_requests(): void
    {
        $this->team->assignedCompanies()->attach($this->companyA->id, ['assigned_by' => $this->admin->id]);

        $outsideCampaign = Campaign::create([
            'brand_id' => $this->brandB1->id,
            'name' => 'Outside Campaign',
            'status' => 'Running',
        ]);

        $this->actingAs($this->team)
            ->getJson("/api/v1/admin/campaigns/{$outsideCampaign->id}")
            ->assertForbidden();

        $this->actingAs($this->team)->postJson('/api/v1/admin/campaigns', [
            'name' => 'Injected Campaign',
            'brand_id' => $this->brandB1->id,
            'status' => 'Draft',
        ])->assertNotFound();

        $this->actingAs($this->team)->postJson('/api/v1/admin/tasks', [
            'name' => 'Injected Task',
            'campaign_id' => $outsideCampaign->id,
            'progress_status' => 'NotStarted',
        ])->assertNotFound();

        $this->actingAs($this->team)->postJson('/api/v1/admin/products', [
            'code' => 'OUTSIDE-001',
            'name' => 'Injected Product',
            'brand_id' => $this->brandB1->id,
            'status' => 'Active',
        ])->assertNotFound();

        $this->actingAs($this->team)->postJson('/api/v1/admin/promotions', [
            'name' => 'Injected Promotion',
            'brand_id' => $this->brandB1->id,
            'status' => 'Pending',
        ])->assertNotFound();

        $this->assertDatabaseMissing('campaigns', ['name' => 'Injected Campaign']);
        $this->assertDatabaseMissing('tasks', ['name' => 'Injected Task']);
        $this->assertDatabaseMissing('products', ['code' => 'OUTSIDE-001']);
        $this->assertDatabaseMissing('promotions', ['name' => 'Injected Promotion']);
    }
}
