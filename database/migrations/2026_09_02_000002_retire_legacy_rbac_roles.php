<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FINAL_ROLES = ['Super Admin', 'Admin', 'Tim'];

    public function up(): void
    {
        $assignedLegacyRoles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereNotIn('roles.name', self::FINAL_ROLES)
            ->count();

        if ($assignedLegacyRoles > 0) {
            throw new RuntimeException('Legacy roles are still assigned. Complete the RBAC user migration before cleanup.');
        }

        Schema::create('rbac_legacy_role_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->unique();
            $table->string('name');
            $table->string('guard_name');
            $table->json('permissions');
            $table->timestamps();
        });

        $legacyRoles = DB::table('roles')
            ->whereNotIn('name', self::FINAL_ROLES)
            ->orderBy('id')
            ->get();

        foreach ($legacyRoles as $role) {
            $permissions = DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $role->id)
                ->orderBy('permissions.name')
                ->pluck('permissions.name')
                ->all();

            DB::table('rbac_legacy_role_snapshots')->insert([
                'role_id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('roles')->whereNotIn('name', self::FINAL_ROLES)->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('rbac_legacy_role_snapshots')) {
            return;
        }

        foreach (DB::table('rbac_legacy_role_snapshots')->orderBy('role_id')->get() as $snapshot) {
            DB::table('roles')->insertOrIgnore([
                'id' => $snapshot->role_id,
                'name' => $snapshot->name,
                'guard_name' => $snapshot->guard_name,
                'created_at' => $snapshot->created_at,
                'updated_at' => $snapshot->updated_at,
            ]);

            $roleId = DB::table('roles')
                ->where('name', $snapshot->name)
                ->where('guard_name', $snapshot->guard_name)
                ->value('id');

            $permissionNames = json_decode($snapshot->permissions, true, flags: JSON_THROW_ON_ERROR);
            $permissionIds = DB::table('permissions')
                ->where('guard_name', $snapshot->guard_name)
                ->whereIn('name', $permissionNames)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        Schema::drop('rbac_legacy_role_snapshots');
    }
};
