<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbac_legacy_user_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('user_type', 20)->nullable();
            $table->json('roles');
            $table->json('direct_permissions');
            $table->json('effective_permissions');
            $table->json('company_assignments');
            $table->json('brand_assignments');
            $table->string('target_role')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('migrated_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_legacy_user_snapshots');
    }
};
