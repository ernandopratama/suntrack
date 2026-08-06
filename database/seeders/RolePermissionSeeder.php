<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'campaign.view',
            'campaign.create',
            'campaign.update',
            'campaign.delete',
            'campaign.approve',
            'promotion.view',
            'promotion.create',
            'promotion.update',
            'promotion.delete',
            'promotion.approve',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'variant.view',
            'variant.create',
            'variant.update',
            'variant.delete',
            'review.link.create',
            'review.link.view',
            'review.link.revoke',
            'settings.view',
            'settings.update',
            'audit.view',
            'report.export',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // Create Roles and Assign Permissions (both web and api guards)
        foreach (['web', 'api'] as $guard) {
            // 1. Super Admin
            $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
            $superAdmin->givePermissionTo(Permission::where('guard_name', $guard)->get());

            // 2. Brand Manager
            $brandManager = Role::firstOrCreate(['name' => 'Brand Manager', 'guard_name' => $guard]);
            $brandManager->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                'campaign.view',
                'campaign.create',
                'campaign.update',
                'promotion.view',
                'promotion.create',
                'promotion.update',
                'product.view',
                'variant.view',
                'review.link.create',
                'review.link.view',
                'report.export',
            ])->get());

            // 3. Finance Auditor
            $financeAuditor = Role::firstOrCreate(['name' => 'Finance Auditor', 'guard_name' => $guard]);
            $financeAuditor->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                'campaign.view',
                'promotion.view',
                'promotion.approve',
                'product.view',
                'variant.view',
                'audit.view',
                'report.export',
            ])->get());

            // 4. Operational Staff
            $opStaff = Role::firstOrCreate(['name' => 'Operational Staff', 'guard_name' => $guard]);
            $opStaff->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                'campaign.view',
                'promotion.view',
                'product.view',
                'product.create',
                'product.update',
                'variant.view',
                'variant.create',
                'variant.update',
            ])->get());

            // 5. Company (hanya akses public review)
            $company = Role::firstOrCreate(['name' => 'Company', 'guard_name' => $guard]);
            // Company tidak perlu permission khusus, hanya akses public via token
        }

        // Assign Super Admin role to default admin user if exists
        $adminUser = User::where('email', 'admin@suntrack.com')->first();
        if ($adminUser && !$adminUser->hasRole('Super Admin')) {
            $adminUser->assignRole('Super Admin');
        }
    }
}
