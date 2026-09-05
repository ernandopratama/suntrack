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
    private const ENTERPRISE_ADMIN_PERMISSIONS = [
        'task.review',
        'performance-report.view',
        'performance-report.create',
        'performance-report.update',
        'performance-report.delete',
        'performance-report.review',
        'performance-report.publish',
    ];

    private const ENTERPRISE_TEAM_PERMISSIONS = [
        'performance-report.view',
        'performance-report.create',
        'performance-report.update',
        'performance-report.delete',
    ];

    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        foreach (RbacRegistry::GUARDS as $guard) {
            $createdPermissions = [];
            foreach (RbacRegistry::PERMISSIONS as $permission) {
                $permissionModel = Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
                if ($permissionModel->wasRecentlyCreated) {
                    $createdPermissions[] = $permission;
                }
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
            } else {
                $admin->givePermissionTo(array_values(array_intersect(
                    $createdPermissions,
                    self::ENTERPRISE_ADMIN_PERMISSIONS
                )));
            }

            if ($team->wasRecentlyCreated) {
                $team->syncPermissions([]);
            }
            $newTeamPermissions = array_values(array_intersect(
                $createdPermissions,
                self::ENTERPRISE_TEAM_PERMISSIONS
            ));
            if ($newTeamPermissions !== []) {
                User::role(RbacRegistry::TEAM, $guard)->each(
                    fn (User $user) => $user->givePermissionTo($newTeamPermissions)
                );
            }
        }

        $adminUser = User::where('email', 'admin@suntrack.com')->first();
        if ($adminUser && ! $adminUser->hasRole(RbacRegistry::SUPER_ADMIN)) {
            $adminUser->assignRole(RbacRegistry::SUPER_ADMIN);
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}
