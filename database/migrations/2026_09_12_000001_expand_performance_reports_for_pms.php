<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_reports', function (Blueprint $table) {
            $table->decimal('turnover', 15, 2)->default(0)->after('period_end');
            $table->unsignedInteger('order_count')->default(0)->after('turnover');
            $table->decimal('ad_spend', 15, 2)->default(0)->after('order_count');
            $table->decimal('ad_sales', 15, 2)->default(0)->after('ad_spend');
            $table->longText('findings')->nullable()->after('content');
            $table->longText('action_plan')->nullable()->after('findings');
        });

        Schema::create('performance_report_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('performance_report_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->string('title')->nullable();
            $table->longText('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['performance_report_id', 'sort_order'], 'report_media_order_idx');
        });
    }

    public function down(): void
    {
        $hasMedia = Schema::hasTable('performance_report_media')
            && DB::table('performance_report_media')->exists();
        $hasPmsData = DB::table('performance_reports')->where(function ($query) {
            $query->where('turnover', '!=', 0)
                ->orWhere('order_count', '!=', 0)
                ->orWhere('ad_spend', '!=', 0)
                ->orWhere('ad_sales', '!=', 0)
                ->orWhereNotNull('findings')
                ->orWhereNotNull('action_plan');
        })->exists();

        if ($hasMedia || $hasPmsData) {
            throw new RuntimeException('Rollback ditolak: data PMS harus diekspor atau dihapus secara eksplisit.');
        }

        Schema::dropIfExists('performance_report_media');
        Schema::table('performance_reports', function (Blueprint $table) {
            $table->dropColumn([
                'turnover', 'order_count', 'ad_spend', 'ad_sales', 'findings', 'action_plan',
            ]);
        });
    }
};
