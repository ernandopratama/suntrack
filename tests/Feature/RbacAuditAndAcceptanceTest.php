<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Services\Authorization\UserAccessService;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAuditAndAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_access_update_is_audited_and_applies_to_current_session_immediately(): void
    {
        $companyA = Company::create(['name' => 'Company A']);
        $companyB = Company::create(['name' => 'Company B']);
        $brandA = Brand::create(['company_id' => $companyA->id, 'name' => 'Brand A']);
        $brandB = Brand::create(['company_id' => $companyB->id, 'name' => 'Brand B']);

        $admin = User::factory()->create();
        $admin->assignRole(RbacRegistry::ADMIN);
        $team = User::factory()->create();
        $team->assignRole(RbacRegistry::TEAM);
        $team->syncPermissions(['campaign.view']);
        $team->assignedCompanies()->attach($companyA->id, ['assigned_by' => $admin->id]);

        $this->actingAs($team)->getJson('/api/v1/auth/user')->assertOk();

        $this->actingAs($admin)->putJson("/api/v1/admin/users/{$team->id}", [
            'name' => $team->name,
            'username' => $team->username,
            'email' => $team->email,
            'permissions' => ['product.view', 'activity.view'],
            'company_ids' => [$companyB->id],
            'brand_ids' => [],
        ])->assertOk();

        $log = ActivityLog::query()
            ->where('loggable_id', $team->id)
            ->sole();

        $this->assertSame(ActivityType::AccessUpdated->value, $log->action);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame($team->id, $log->properties['target_user_id']);
        $this->assertSame([$companyA->id], $log->properties['before']['company_ids']);
        $this->assertSame([$companyB->id], $log->properties['after']['company_ids']);
        $this->assertSame(['campaign.view'], $log->properties['before']['direct_permissions']);
        $this->assertSame(['activity.view', 'product.view'], $log->properties['after']['direct_permissions']);

        $profile = $this->actingAs($team)->getJson('/api/v1/auth/user')->assertOk();
        $this->assertEqualsCanonicalizing(
            ['activity.view', 'product.view'],
            $profile->json('data.user.permissions')
        );
        $this->assertSame([$companyB->id], $profile->json('data.user.scope.company_ids'));
        $this->assertSame([$brandB->id], $profile->json('data.user.scope.brand_ids'));
        $this->assertNotContains($brandA->id, $profile->json('data.user.scope.brand_ids'));
    }

    public function test_scope_summary_cache_is_invalidated_explicitly(): void
    {
        $companyA = Company::create(['name' => 'Company A']);
        $companyB = Company::create(['name' => 'Company B']);
        $admin = User::factory()->create();
        $admin->assignRole(RbacRegistry::ADMIN);
        $team = User::factory()->create();
        $team->assignRole(RbacRegistry::TEAM);
        $team->assignedCompanies()->attach($companyA->id, ['assigned_by' => $admin->id]);

        $scope = app(DataScopeService::class);
        $this->assertSame([$companyA->id], $scope->effectiveCompanyIds($team)->all());

        $team->assignedCompanies()->syncWithPivotValues([$companyB->id], ['assigned_by' => $admin->id]);
        $this->assertSame([$companyA->id], $scope->effectiveCompanyIds($team)->all());

        app(UserAccessService::class)->invalidate($team);
        $this->assertSame([$companyB->id], $scope->effectiveCompanyIds($team)->all());
    }

    public function test_user_creation_and_deletion_store_complete_access_snapshots(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RbacRegistry::SUPER_ADMIN);

        $created = $this->actingAs($superAdmin)->postJson('/api/v1/admin/users', [
            'name' => 'Audited Team',
            'username' => 'audited.team',
            'email' => 'audited.team@example.test',
            'password' => 'Password123!',
            'role' => RbacRegistry::TEAM,
            'permissions' => ['campaign.view'],
            'company_ids' => [],
            'brand_ids' => [],
        ])->assertCreated()->json('data.user');

        $createdLog = ActivityLog::query()
            ->where('loggable_id', $created['id'])
            ->where('action', ActivityType::Created->value)
            ->sole();
        $this->assertNull($createdLog->properties['before']);
        $this->assertSame([RbacRegistry::TEAM], $createdLog->properties['after']['roles']);
        $this->assertSame(['campaign.view'], $createdLog->properties['after']['direct_permissions']);

        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/admin/users/{$created['id']}")
            ->assertOk();

        $deletedLog = ActivityLog::query()
            ->where('loggable_id', $created['id'])
            ->where('action', ActivityType::Deleted->value)
            ->sole();
        $this->assertSame([RbacRegistry::TEAM], $deletedLog->properties['before']['roles']);
        $this->assertNull($deletedLog->properties['after']);
    }

    public function test_mutating_resource_routes_keep_permission_middleware(): void
    {
        $expected = [
            'campaigns.store' => 'permission:campaign.create',
            'campaigns.update' => 'permission:campaign.update',
            'campaigns.destroy' => 'permission:campaign.delete',
            'companies.store' => 'permission:company.create',
            'companies.update' => 'permission:company.update',
            'companies.destroy' => 'permission:company.delete',
            'brands.store' => 'permission:brand.create',
            'brands.update' => 'permission:brand.update',
            'brands.destroy' => 'permission:brand.delete',
            'tasks.store' => 'permission:task.create',
            'tasks.update' => 'permission:task.update',
            'tasks.destroy' => 'permission:task.delete',
            'promotions.store' => 'permission:promotion.create',
            'promotions.update' => 'permission:promotion.update',
            'promotions.destroy' => 'permission:promotion.delete',
            'products.store' => 'permission:product.create',
            'products.update' => 'permission:product.update',
            'products.destroy' => 'permission:product.delete',
        ];

        foreach ($expected as $routeName => $middleware) {
            $route = app('router')->getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} is missing.");
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }

    public function test_new_rbac_views_use_theme_tokens_and_access_guards(): void
    {
        $themeCss = file_get_contents(resource_path('css/app.css'));
        $themeStore = file_get_contents(resource_path('js/stores/theme.js'));
        $router = file_get_contents(resource_path('js/router.js'));

        $this->assertStringContainsString(":root[data-theme='light']", $themeCss);
        $this->assertStringContainsString(":root[data-theme='dark']", $themeCss);
        $this->assertStringContainsString('document.documentElement.dataset.theme', $themeStore);
        $this->assertStringContainsString("path: 'forbidden'", $router);

        foreach ([
            'js/pages/Users.vue',
            'js/components/UserForm.vue',
            'js/pages/AccessDenied.vue',
            'js/pages/ActivityLogs.vue',
            'js/pages/ExportPage.vue',
        ] as $path) {
            $source = file_get_contents(resource_path($path));
            $this->assertStringContainsString('bg-surface', $source);
            $this->assertStringContainsString('text-content', $source);
        }
    }

    public function test_uuid_user_can_issue_and_use_a_sanctum_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RbacRegistry::SUPER_ADMIN);

        $token = $user->createToken('rbac-acceptance')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', RbacRegistry::SUPER_ADMIN);
    }
}
