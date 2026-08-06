<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained()->restrictOnDelete();
            $table->string('reviewer_name', 150);
            $table->string('reviewer_position', 150)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->string('old_status', 50);
            $table->string('new_status', 50);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
