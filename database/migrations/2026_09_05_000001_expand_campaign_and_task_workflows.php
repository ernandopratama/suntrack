<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const CAMPAIGN_STATUS_MAP = [
        'Draft' => 'draft',
        'Waiting Approval' => 'waiting_review',
        'Approved' => 'approved',
        'Running' => 'in_progress',
        'Finished' => 'completed',
        'Completed' => 'completed',
        'Cancelled' => 'cancelled',
    ];

    /** @var array<string, string> */
    private const TASK_STATUS_MAP = [
        'NotStarted' => 'pending',
        'Not Started' => 'pending',
        'InProgress' => 'in_progress',
        'In Progress' => 'in_progress',
        'OnHold' => 'on_hold',
        'On Hold' => 'on_hold',
        'Revision' => 'revision',
        'Completed' => 'completed',
    ];

    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('objective')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->text('approval_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->index(['brand_id', 'status']);
            $table->index(['pic_id', 'status']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->uuid('campaign_id')->nullable()->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->restrictOnDelete();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->restrictOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->text('notes')->nullable();
            $table->text('hold_reason')->nullable();
            $table->text('revision_notes')->nullable();
            $table->text('completion_summary')->nullable();
            $table->text('completion_details')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->index(['brand_id', 'progress_status']);
            $table->index(['assignee_id', 'progress_status']);
        });

        DB::table('campaigns')->orderBy('id')->each(function (object $campaign): void {
            DB::table('campaigns')->where('id', $campaign->id)->update([
                'created_by' => $campaign->pic_id,
                'status' => self::CAMPAIGN_STATUS_MAP[$campaign->status] ?? $campaign->status,
            ]);
        });

        DB::table('tasks')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'tasks.campaign_id')
            ->select('tasks.id', 'tasks.progress_status', 'campaigns.brand_id', 'campaigns.created_by')
            ->orderBy('tasks.id')
            ->each(function (object $task): void {
                DB::table('tasks')->where('id', $task->id)->update([
                    'brand_id' => $task->brand_id,
                    'created_by' => $task->created_by,
                    'progress_status' => self::TASK_STATUS_MAP[$task->progress_status] ?? $task->progress_status,
                ]);
            });
    }

    public function down(): void
    {
        if (DB::table('tasks')->whereNull('campaign_id')->exists()) {
            throw new RuntimeException('Rollback ditolak: terdapat Task mandiri tanpa campaign_id. Hubungkan Task ke Campaign sebelum rollback.');
        }
        if (DB::table('campaigns')
            ->where(function ($query) {
                $query->whereNotNull('objective')
                    ->orWhereNotNull('approval_notes')
                    ->orWhereNotNull('completed_at')
                    ->orWhere('priority', '!=', 'normal')
                    ->orWhereColumn('created_by', '!=', 'pic_id')
                    ->orWhere(function ($creator) {
                        $creator->whereNotNull('created_by')->whereNull('pic_id');
                    });
            })->exists()) {
            throw new RuntimeException('Rollback ditolak: terdapat data Campaign baru yang tidak dapat dipertahankan oleh schema lama.');
        }
        if (DB::table('tasks')
            ->where(function ($query) {
                $query->whereNotNull('description')
                    ->orWhereNotNull('pic_id')
                    ->orWhereNotNull('assignee_id')
                    ->orWhereNotNull('notes')
                    ->orWhereNotNull('hold_reason')
                    ->orWhereNotNull('revision_notes')
                    ->orWhereNotNull('completion_summary')
                    ->orWhereNotNull('completion_details')
                    ->orWhereNotNull('completed_at')
                    ->orWhere('priority', '!=', 'normal');
            })->exists()) {
            throw new RuntimeException('Rollback ditolak: terdapat data Task baru yang tidak dapat dipertahankan oleh schema lama.');
        }

        $campaignReverse = [
            'draft' => 'Draft',
            'assigned' => 'Draft',
            'in_progress' => 'Running',
            'waiting_review' => 'Waiting Approval',
            'revision' => 'Waiting Approval',
            'approved' => 'Approved',
            'completed' => 'Finished',
            'cancelled' => 'Cancelled',
        ];
        $taskReverse = [
            'pending' => 'NotStarted',
            'assigned' => 'NotStarted',
            'in_progress' => 'InProgress',
            'on_hold' => 'OnHold',
            'waiting_review' => 'InProgress',
            'revision' => 'Revision',
            'completed' => 'Completed',
            'cancelled' => 'OnHold',
        ];

        DB::table('campaigns')->orderBy('id')->each(function (object $campaign) use ($campaignReverse): void {
            DB::table('campaigns')->where('id', $campaign->id)->update([
                'status' => $campaignReverse[$campaign->status] ?? $campaign->status,
            ]);
        });

        DB::table('tasks')->orderBy('id')->each(function (object $task) use ($taskReverse): void {
            DB::table('tasks')->where('id', $task->id)->update([
                'progress_status' => $taskReverse[$task->progress_status] ?? $task->progress_status,
            ]);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['pic_id']);
            $table->dropForeign(['assignee_id']);
            $table->dropIndex(['brand_id', 'progress_status']);
            $table->dropIndex(['assignee_id', 'progress_status']);
            $table->dropColumn([
                'brand_id', 'created_by', 'pic_id', 'assignee_id', 'description', 'priority', 'notes',
                'hold_reason', 'revision_notes', 'completion_summary', 'completion_details', 'completed_at',
            ]);
            $table->uuid('campaign_id')->nullable(false)->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->restrictOnDelete();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['brand_id', 'status']);
            $table->dropIndex(['pic_id', 'status']);
            $table->dropColumn(['created_by', 'objective', 'priority', 'approval_notes', 'completed_at']);
        });
    }
};
