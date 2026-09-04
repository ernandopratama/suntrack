<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole(RbacRegistry::SUPER_ADMIN);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RbacRegistry::ADMIN);

        $this->team = User::factory()->create();
        $this->team->assignRole(RbacRegistry::TEAM);
    }

    public function test_super_admin_can_list_only_three_final_roles_with_user_counts(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/admin/roles');

        $response->assertOk()
            ->assertJsonCount(3, 'data.roles')
            ->assertJsonPath('data.roles.0.name', RbacRegistry::SUPER_ADMIN)
            ->assertJsonPath('data.roles.1.name', RbacRegistry::ADMIN)
            ->assertJsonPath('data.roles.2.name', RbacRegistry::TEAM)
            ->assertJsonPath('data.roles.2.users_count', 1)
            ->assertJsonPath('data.roles.0.editable', false)
            ->assertJsonPath('data.roles.1.editable', true);
    }

    public function test_admin_and_team_cannot_access_role_management_api(): void
    {
        $role = Role::findByName(RbacRegistry::TEAM, 'web');

        foreach ([$this->admin, $this->team] as $actor) {
            $this->actingAs($actor)->getJson('/api/v1/admin/roles')->assertForbidden();
            $this->actingAs($actor)
                ->getJson("/api/v1/admin/roles/{$role->id}/users")
                ->assertForbidden();
            $this->actingAs($actor)
                ->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permissions' => []])
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_update_role_permissions_for_both_guards_and_change_is_audited(): void
    {
        $role = Role::findByName(RbacRegistry::ADMIN, 'web');
        $permissions = ['campaign.view', 'product.view'];

        $this->actingAs($this->superAdmin)
            ->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
                'permissions' => $permissions,
            ])
            ->assertOk()
            ->assertJsonPath('data.role.name', RbacRegistry::ADMIN);

        foreach (RbacRegistry::GUARDS as $guard) {
            $this->assertEqualsCanonicalizing(
                $permissions,
                Role::findByName(RbacRegistry::ADMIN, $guard)->permissions->pluck('name')->all()
            );
        }

        $log = ActivityLog::query()
            ->where('actor_id', $this->superAdmin->id)
            ->where('action', ActivityType::AccessUpdated->value)
            ->latest('created_at')
            ->firstOrFail();

        $this->assertSame(RbacRegistry::ADMIN, $log->properties['target_role']);
        $this->assertEqualsCanonicalizing($permissions, $log->properties['after']);

        $this->seed(RolePermissionSeeder::class);
        $this->assertEqualsCanonicalizing(
            $permissions,
            Role::findByName(RbacRegistry::ADMIN, 'web')->permissions->pluck('name')->all()
        );
    }

    public function test_role_permission_update_enforces_whitelist_and_super_admin_lock(): void
    {
        $teamRole = Role::findByName(RbacRegistry::TEAM, 'web');
        $superAdminRole = Role::findByName(RbacRegistry::SUPER_ADMIN, 'web');

        $this->actingAs($this->superAdmin)
            ->putJson("/api/v1/admin/roles/{$teamRole->id}/permissions", [
                'permissions' => ['user.view'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0']);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/v1/admin/roles/{$superAdminRole->id}/permissions", [
                'permissions' => [],
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_view_active_users_owned_by_a_role(): void
    {
        $otherTeam = User::factory()->create();
        $otherTeam->assignRole(RbacRegistry::TEAM);
        $deletedTeam = User::factory()->create();
        $deletedTeam->assignRole(RbacRegistry::TEAM);
        $deletedTeam->delete();

        $role = Role::findByName(RbacRegistry::TEAM, 'web');

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/api/v1/admin/roles/{$role->id}/users?per_page=100");

        $response->assertOk()
            ->assertJsonPath('data.role.name', RbacRegistry::TEAM)
            ->assertJsonPath('data.users.total', 2)
            ->assertJsonFragment(['id' => $this->team->id])
            ->assertJsonFragment(['id' => $otherTeam->id])
            ->assertJsonMissing(['id' => $deletedTeam->id]);
    }

    public function test_role_creation_and_deletion_routes_are_not_exposed(): void
    {
        $role = Role::findByName(RbacRegistry::TEAM, 'web');

        $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/admin/roles', ['name' => 'Custom Role'])
            ->assertMethodNotAllowed();
        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/v1/admin/roles/{$role->id}")
            ->assertMethodNotAllowed();
    }

    public function test_frontend_exposes_super_admin_role_menu_page_and_user_modal(): void
    {
        $layout = File::get(resource_path('js/layouts/AdminLayout.vue'));
        $router = File::get(resource_path('js/router.js'));
        $page = File::get(resource_path('js/pages/Roles.vue'));

        $this->assertStringContainsString("v-if=\"\$hasRole('Super Admin')\"", $layout);
        $this->assertStringContainsString('to="/roles"', $layout);
        $this->assertStringContainsString("meta: { role: 'Super Admin' }", $router);
        $this->assertStringContainsString('User Role {{ selectedRole?.name }}', $page);
        $this->assertStringContainsString('Simpan Permission', $page);
    }
}
