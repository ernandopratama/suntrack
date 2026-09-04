<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_contains_three_final_roles_and_valid_permission_sets(): void
    {
        $this->assertSame(
            [RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN, RbacRegistry::TEAM],
            RbacRegistry::ROLES
        );
        $this->assertCount(count(array_unique(RbacRegistry::PERMISSIONS)), RbacRegistry::PERMISSIONS);
        $this->assertEmpty(array_diff(RbacRegistry::ADMIN_PERMISSIONS, RbacRegistry::PERMISSIONS));
        $this->assertEmpty(array_diff(RbacRegistry::TEAM_DEFAULT_PERMISSIONS, RbacRegistry::TEAM_ALLOWED_PERMISSIONS));
        $this->assertEmpty(array_diff(RbacRegistry::TEAM_ALLOWED_PERMISSIONS, RbacRegistry::PERMISSIONS));
        $this->assertNotContains('user.delete', RbacRegistry::ADMIN_PERMISSIONS);
        $this->assertNotContains('access.assign-role', RbacRegistry::ADMIN_PERMISSIONS);
        $this->assertNotContains('company.create', RbacRegistry::TEAM_ALLOWED_PERMISSIONS);
        $this->assertNotContains('brand.create', RbacRegistry::TEAM_ALLOWED_PERMISSIONS);
    }

    public function test_role_permission_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        foreach (RbacRegistry::GUARDS as $guard) {
            $this->assertSame(
                $this->sorted(RbacRegistry::ROLES),
                $this->sorted(Role::query()->where('guard_name', $guard)->pluck('name')->all())
            );
            $this->assertSame(
                $this->sorted(RbacRegistry::PERMISSIONS),
                $this->sorted(Permission::query()->where('guard_name', $guard)->pluck('name')->all())
            );
            $this->assertSame(
                $this->sorted(RbacRegistry::PERMISSIONS),
                $this->rolePermissions(RbacRegistry::SUPER_ADMIN, $guard)
            );
            $this->assertSame(
                $this->sorted(RbacRegistry::ADMIN_PERMISSIONS),
                $this->rolePermissions(RbacRegistry::ADMIN, $guard)
            );
            $this->assertSame(
                [],
                $this->rolePermissions(RbacRegistry::TEAM, $guard)
            );
        }
    }

    public function test_production_seeder_is_safe_to_run_repeatedly(): void
    {
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        foreach (RbacRegistry::GUARDS as $guard) {
            $this->assertSame(
                $this->sorted(RbacRegistry::ROLES),
                $this->sorted(Role::query()->where('guard_name', $guard)->pluck('name')->all())
            );
            $this->assertSame(
                $this->sorted(RbacRegistry::PERMISSIONS),
                $this->sorted(Permission::query()->where('guard_name', $guard)->pluck('name')->all())
            );
        }
    }

    public function test_assignment_tables_store_company_and_brand_assignments(): void
    {
        $this->assertTrue(Schema::hasColumns('user_company_assignments', [
            'id', 'user_id', 'company_id', 'assigned_by', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('user_brand_assignments', [
            'id', 'user_id', 'brand_id', 'assigned_by', 'created_at', 'updated_at',
        ]));

        $company = Company::create(['name' => 'Assigned Company']);
        $brand = Brand::create(['company_id' => $company->id, 'name' => 'Assigned Brand']);
        $user = User::factory()->create();
        $now = now();

        DB::table('user_company_assignments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_id' => $company->id,
            'assigned_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('user_brand_assignments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'assigned_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertDatabaseHas('user_company_assignments', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
        $this->assertDatabaseHas('user_brand_assignments', [
            'user_id' => $user->id,
            'brand_id' => $brand->id,
        ]);

        $user->forceDelete();

        $this->assertDatabaseMissing('user_company_assignments', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_brand_assignments', ['user_id' => $user->id]);
    }

    public function test_company_assignment_rejects_duplicate_user_and_company(): void
    {
        $company = Company::create(['name' => 'Unique Company']);
        $user = User::factory()->create();
        $assignment = [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'assigned_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('user_company_assignments')->insert([
            ...$assignment,
            'id' => (string) Str::uuid(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('user_company_assignments')->insert([
            ...$assignment,
            'id' => (string) Str::uuid(),
        ]);
    }

    public function test_brand_assignment_rejects_duplicate_user_and_brand(): void
    {
        $company = Company::create(['name' => 'Unique Brand Company']);
        $brand = Brand::create(['company_id' => $company->id, 'name' => 'Unique Brand']);
        $user = User::factory()->create();
        $assignment = [
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'assigned_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('user_brand_assignments')->insert([
            ...$assignment,
            'id' => (string) Str::uuid(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('user_brand_assignments')->insert([
            ...$assignment,
            'id' => (string) Str::uuid(),
        ]);
    }

    public function test_assignment_migrations_can_be_rolled_back_and_reapplied(): void
    {
        $brandMigration = require database_path('migrations/2026_08_29_000003_create_user_brand_assignments_table.php');
        $companyMigration = require database_path('migrations/2026_08_29_000002_create_user_company_assignments_table.php');

        $brandMigration->down();
        $companyMigration->down();

        $this->assertFalse(Schema::hasTable('user_brand_assignments'));
        $this->assertFalse(Schema::hasTable('user_company_assignments'));
        $this->assertFalse(Schema::hasColumn('users', 'company_id'));

        $companyMigration->up();
        $brandMigration->up();

        $this->assertTrue(Schema::hasTable('user_company_assignments'));
        $this->assertTrue(Schema::hasTable('user_brand_assignments'));
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function rolePermissions(string $role, string $guard): array
    {
        return $this->sorted(
            Role::findByName($role, $guard)->permissions->pluck('name')->all()
        );
    }
}
