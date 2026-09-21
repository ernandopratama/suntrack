<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_personal')->default(false)->after('created_by');
            $table->string('recurrence_type', 20)->nullable()->after('deadline');
            $table->dateTime('recurrence_ends_at')->nullable()->after('recurrence_type');
            $table->dateTime('next_recurrence_at')->nullable()->after('recurrence_ends_at');
            $table->boolean('recurrence_notify')->default(true)->after('next_recurrence_at');
            $table->foreignUuid('recurrence_source_id')->nullable()->after('recurrence_notify')
                ->constrained('tasks')->nullOnDelete();
            $table->dateTime('recurrence_occurrence_at')->nullable()->after('recurrence_source_id');
            $table->index(['recurrence_type', 'next_recurrence_at'], 'tasks_recurrence_due_idx');
            $table->unique(
                ['recurrence_source_id', 'recurrence_occurrence_at'],
                'tasks_recurrence_occurrence_unique'
            );
        });
    }

    public function down(): void
    {
        if (DB::table('tasks')->where(function ($query) {
            $query->where('is_personal', true)
                ->orWhereNotNull('recurrence_type')
                ->orWhereNotNull('recurrence_ends_at')
                ->orWhereNotNull('next_recurrence_at')
                ->orWhereNotNull('recurrence_source_id')
                ->orWhereNotNull('recurrence_occurrence_at');
        })->exists()) {
            throw new RuntimeException('Rollback ditolak: terdapat task pribadi atau task berulang yang tidak dapat dipertahankan oleh skema lama.');
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_recurrence_occurrence_unique');
            $table->dropIndex('tasks_recurrence_due_idx');
            $table->dropForeign(['recurrence_source_id']);
            $table->dropColumn([
                'is_personal',
                'recurrence_type',
                'recurrence_ends_at',
                'next_recurrence_at',
                'recurrence_notify',
                'recurrence_source_id',
                'recurrence_occurrence_at',
            ]);
        });
    }
};
