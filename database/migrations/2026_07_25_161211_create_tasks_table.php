<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('progress_status', 50)->default('NotStarted');
            $table->boolean('requires_visual')->default(false);
            $table->string('visual_type', 100)->nullable();
            $table->json('creative_brief')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
