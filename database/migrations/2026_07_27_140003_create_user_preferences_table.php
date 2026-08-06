<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User Preferences — per-user UI and dashboard personalization settings (Sprint 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete()->unique();

            // Navigation & layout preferences
            $table->string('default_landing_page', 100)->default('/dashboard')
                ->comment('Path to redirect after login, e.g. /campaigns, /promotions');
            $table->unsignedSmallInteger('default_page_size')->default(15)
                ->comment('Default pagination size (10, 15, 25, 50, 100)');

            // UI preferences
            $table->string('theme', 20)->default('dark')
                ->comment('dark | light | system');
            $table->string('locale', 10)->default('id')
                ->comment('Language/locale preference: id, en');
            $table->string('timezone', 50)->default('Asia/Jakarta');

            // Dashboard widget personalization (JSON array of visible widget keys in order)
            $table->json('dashboard_widgets')->nullable()
                ->comment('Ordered array of widget keys the user wants visible on their dashboard');

            // Extended preferences (for future feature expansion)
            $table->json('extended')->nullable()
                ->comment('Future-proof JSON bag for additional preference keys');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
