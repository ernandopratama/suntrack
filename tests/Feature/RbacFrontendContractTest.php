<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use App\Services\Authorization\UserAccessService;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacFrontendContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_auth_profile_returns_effective_role_permissions_and_team_scope(): void
    {
        $companyA = Company::create(['name' => 'Company A']);
        $companyB = Company::create(['name' => 'Company B']);
        $brandA = Brand::create(['company_id' => $companyA->id, 'name' => 'Brand A']);
        $brandB = Brand::create(['company_id' => $companyB->id, 'name' => 'Brand B']);

        $admin = User::factory()->create();
        $admin->assignRole(RbacRegistry::ADMIN);

        $team = User::factory()->create();
        $team->assignRole(RbacRegistry::TEAM);
        $team->syncPermissions(['campaign.view', 'activity.view']);
        $team->assignedCompanies()->attach($companyA->id, ['assigned_by' => $admin->id]);
        $team->assignedBrands()->attach($brandB->id, ['assigned_by' => $admin->id]);

        $response = $this->actingAs($team)->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJsonPath('data.user.role', RbacRegistry::TEAM)
            ->assertJsonPath('data.user.scope.global', false);

        $this->assertEqualsCanonicalizing(
            ['activity.view', 'campaign.view'],
            $response->json('data.user.permissions')
        );
        $this->assertEqualsCanonicalizing(
            [$companyA->id, $companyB->id],
            $response->json('data.user.scope.company_ids')
        );
        $this->assertEqualsCanonicalizing(
            [$brandA->id, $brandB->id],
            $response->json('data.user.scope.brand_ids')
        );

        $team->syncPermissions(['product.view']);
        $team->assignedCompanies()->syncWithPivotValues([$companyB->id], ['assigned_by' => $admin->id]);
        $team->assignedBrands()->sync([]);
        app(UserAccessService::class)->invalidate($team);

        $updated = $this->actingAs($team)->getJson('/api/v1/auth/user');
        $updated->assertOk()
            ->assertJsonPath('data.user.permissions.0', 'product.view')
            ->assertJsonPath('data.user.scope.company_ids.0', $companyB->id)
            ->assertJsonPath('data.user.scope.brand_ids.0', $brandB->id);
    }

    public function test_admin_and_super_admin_profiles_have_global_scope_and_effective_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RbacRegistry::ADMIN);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RbacRegistry::SUPER_ADMIN);

        $adminResponse = $this->actingAs($admin)->getJson('/api/v1/auth/user');
        $adminResponse->assertOk()
            ->assertJsonPath('data.user.role', RbacRegistry::ADMIN)
            ->assertJsonPath('data.user.scope.global', true);
        $this->assertEqualsCanonicalizing(
            RbacRegistry::ADMIN_PERMISSIONS,
            $adminResponse->json('data.user.permissions')
        );

        $superResponse = $this->actingAs($superAdmin)->getJson('/api/v1/auth/user');
        $superResponse->assertOk()
            ->assertJsonPath('data.user.role', RbacRegistry::SUPER_ADMIN)
            ->assertJsonPath('data.user.scope.global', true);
        $this->assertEqualsCanonicalizing(
            RbacRegistry::PERMISSIONS,
            $superResponse->json('data.user.permissions')
        );
    }

    public function test_access_options_follow_actor_role_and_team_cannot_open_user_access(): void
    {
        $company = Company::create(['name' => 'Company A']);
        $brand = Brand::create(['company_id' => $company->id, 'name' => 'Brand A']);

        $admin = User::factory()->create();
        $admin->assignRole(RbacRegistry::ADMIN);

        $team = User::factory()->create();
        $team->assignRole(RbacRegistry::TEAM);
        $team->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/access/options');
        $response->assertOk()
            ->assertJsonPath('data.roles.0', RbacRegistry::TEAM)
            ->assertJsonPath('data.companies.0.id', $company->id)
            ->assertJsonPath('data.brands.0.id', $brand->id);
        $this->assertEqualsCanonicalizing(
            RbacRegistry::TEAM_ALLOWED_PERMISSIONS,
            $response->json('data.team_permissions')
        );

        $this->actingAs($team)
            ->getJson('/api/v1/admin/access/options')
            ->assertForbidden();
        $this->actingAs($team)
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }
}
