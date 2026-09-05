<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_members', function (Blueprint $table) {
            $table->foreignUuid('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['user_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('campaign_members')->exists()) {
            throw new RuntimeException('Rollback ditolak: campaign_members sudah berisi assignment.');
        }

        Schema::dropIfExists('campaign_members');
    }
};
