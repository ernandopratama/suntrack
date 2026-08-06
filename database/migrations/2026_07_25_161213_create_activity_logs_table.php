<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('loggable');
            $table->string('action', 100);
            $table->text('description');
            $table->string('actor_type', 50);
            $table->uuid('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('actor_position')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps(); // Created at represents immutable log timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
