<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 50)->nullable()->after('name');
            });
        }

        foreach (DB::table('users')->select(['id', 'name', 'email'])->whereNull('username')->orderBy('id')->lazy() as $user) {
            $source = $user->email ? Str::before($user->email, '@') : Str::slug($user->name, '_');
            $base = Str::lower((string) preg_replace('/[^a-z0-9._-]/i', '', $source));
            $base = trim($base, '._-') ?: 'user';
            $base = Str::limit($base, 42, '');

            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = Str::limit($base, 42, '') . '_' . $suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
