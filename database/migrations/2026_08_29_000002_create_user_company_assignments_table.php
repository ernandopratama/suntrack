<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_company_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'company_id'], 'user_company_assignments_user_company_unique');
            $table->index('company_id', 'user_company_assignments_company_id_index');
            $table->index('assigned_by', 'user_company_assignments_assigned_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_company_assignments');
    }
};
