<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_brand_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'brand_id'], 'user_brand_assignments_user_brand_unique');
            $table->index('brand_id', 'user_brand_assignments_brand_id_index');
            $table->index('assigned_by', 'user_brand_assignments_assigned_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_brand_assignments');
    }
};
