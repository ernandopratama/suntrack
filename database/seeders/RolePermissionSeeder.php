<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        foreach (RbacRegistry::GUARDS as $guard) {
            foreach (RbacRegistry::PERMISSIONS as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
            }

            $superAdmin = Role::firstOrCreate([
                'name' => RbacRegistry::SUPER_ADMIN,
                'guard_name' => $guard,
            ]);
            $admin = Role::firstOrCreate([
                'name' => RbacRegistry::ADMIN,
                'guard_name' => $guard,
            ]);
            $team = Role::firstOrCreate([
                'name' => RbacRegistry::TEAM,
                'guard_name' => $guard,
            ]);

            $guardPermissions = Permission::query()
                ->where('guard_name', $guard)
                ->whereIn('name', RbacRegistry::PERMISSIONS)
                ->get();

            $superAdmin->syncPermissions($guardPermissions);
            if ($admin->wasRecentlyCreated) {
                $admin->syncPermissions(RbacRegistry::ADMIN_PERMISSIONS);
            }

            if ($team->wasRecentlyCreated) {
                $team->syncPermissions([]);
            }
        }

        $adminUser = User::where('email', 'admin@suntrack.com')->first();
        if ($adminUser && ! $adminUser->hasRole(RbacRegistry::SUPER_ADMIN)) {
            $adminUser->assignRole(RbacRegistry::SUPER_ADMIN);
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}
