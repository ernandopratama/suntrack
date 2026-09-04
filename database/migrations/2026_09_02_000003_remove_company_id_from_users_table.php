<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $unmigratedTeamScopes = DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Tim')
            ->whereNotNull('users.company_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('user_company_assignments')
                    ->whereColumn('user_company_assignments.user_id', 'users.id')
                    ->whereColumn('user_company_assignments.company_id', 'users.company_id');
            })
            ->count();

        if ($unmigratedTeamScopes > 0) {
            throw new RuntimeException('Some Tim company scopes still depend on users.company_id. Migrate them to assignments first.');
        }

        Schema::create('rbac_legacy_user_company_snapshots', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->uuid('company_id');
            $table->timestamps();
        });

        $now = now();
        DB::table('users')
            ->whereNotNull('company_id')
            ->orderBy('id')
            ->select(['id', 'company_id'])
            ->each(function ($user) use ($now) {
                DB::table('rbac_legacy_user_company_snapshots')->insert([
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'company_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->constrained()->restrictOnDelete();
        });

        if (Schema::hasTable('rbac_legacy_user_company_snapshots')) {
            foreach (DB::table('rbac_legacy_user_company_snapshots')->get() as $snapshot) {
                DB::table('users')
                    ->where('id', $snapshot->user_id)
                    ->update(['company_id' => $snapshot->company_id]);
            }

            Schema::drop('rbac_legacy_user_company_snapshots');
        }
    }
};
