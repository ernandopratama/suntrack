<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved Filters — per-user, per-module persistent filter storage (Sprint 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->string('module', 50)->comment('campaigns | promotions | products | variants | activity_logs');
            $table->string('name', 100);
            $table->json('filters')->comment('Serialized filter parameters as key-value map');
            $table->boolean('is_default')->default(false)->comment('One default per user per module');
            $table->timestamps();

            $table->index(['user_id', 'module'], 'sf_user_module_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
