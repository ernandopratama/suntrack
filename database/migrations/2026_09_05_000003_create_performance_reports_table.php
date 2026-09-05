<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('pic_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('supersedes_report_id')->nullable();
            $table->string('report_type', 20);
            $table->string('title');
            $table->date('period_start');
            $table->date('period_end');
            $table->text('executive_summary')->nullable();
            $table->text('content')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->text('review_notes')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index(['author_id', 'status']);
            $table->index(['report_type', 'period_start', 'period_end']);
            $table->unique(['supersedes_report_id', 'version']);
        });

        Schema::table('performance_reports', function (Blueprint $table) {
            $table->foreign('supersedes_report_id')
                ->references('id')
                ->on('performance_reports')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('performance_reports')->exists()) {
            throw new RuntimeException('Rollback ditolak: performance_reports sudah berisi data.');
        }

        Schema::dropIfExists('performance_reports');
    }
};
