<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacLegacyCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_schema_and_role_registry_have_no_active_legacy_contract(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertFalse(Schema::hasColumn('users', 'company_id'));
        $this->assertTrue(Schema::hasTable('rbac_legacy_role_snapshots'));
        $this->assertTrue(Schema::hasTable('rbac_legacy_user_company_snapshots'));
        $this->assertEqualsCanonicalizing(
            RbacRegistry::ROLES,
            Role::query()->distinct()->pluck('name')->all()
        );
    }

    public function test_cleanup_migrations_restore_archived_values_on_rollback_and_can_reapply(): void
    {
        $roleMigration = require database_path('migrations/2026_09_02_000002_retire_legacy_rbac_roles.php');
        $companyMigration = require database_path('migrations/2026_09_02_000003_remove_company_id_from_users_table.php');

        $companyMigration->down();
        $roleMigration->down();

        $company = Company::create(['name' => 'Legacy Company']);
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['company_id' => $company->id]);

        $permission = Permission::create(['name' => 'legacy.view', 'guard_name' => 'web']);
        $legacyRole = Role::create(['name' => 'Brand Manager', 'guard_name' => 'web']);
        $legacyRole->givePermissionTo($permission);

        $roleMigration->up();
        $companyMigration->up();

        $this->assertDatabaseMissing('roles', ['name' => 'Brand Manager', 'guard_name' => 'web']);
        $this->assertFalse(Schema::hasColumn('users', 'company_id'));
        $this->assertDatabaseHas('rbac_legacy_user_company_snapshots', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $companyMigration->down();
        $roleMigration->down();

        $this->assertSame($company->id, DB::table('users')->where('id', $user->id)->value('company_id'));
        $this->assertTrue(Role::findByName('Brand Manager', 'web')->hasPermissionTo('legacy.view'));

        $roleMigration->up();
        $companyMigration->up();

        $this->assertFalse(Schema::hasColumn('users', 'company_id'));
        $this->assertDatabaseMissing('roles', ['name' => 'Brand Manager', 'guard_name' => 'web']);
    }
}
