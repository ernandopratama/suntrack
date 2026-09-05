<?php

namespace App\Services\Workflow;

use App\Enums\CampaignStatus;
use App\Enums\PerformanceReportStatus;
use App\Enums\TaskStatus;
use App\Models\Campaign;
use App\Models\PerformanceReport;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowTransitionService
{
    public function __construct(
        private WorkflowAssignmentService $assignments,
        private TaskReminderService $reminders,
        private NotificationService $notifications
    ) {}

    public function campaign(Campaign $campaign, User $actor, string $target, ?string $note = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $actor, $target, $note): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            $from = $this->canonicalCampaignStatus($campaign->status);
            $to = CampaignStatus::tryFrom($target)?->value;
            $this->assertTarget($to, 'campaign');

            $allowed = [
                'draft' => ['assigned', 'cancelled'],
                'assigned' => ['in_progress', 'cancelled'],
                'in_progress' => ['waiting_review', 'cancelled'],
                'waiting_review' => ['revision', 'approved', 'cancelled'],
                'revision' => ['in_progress', 'cancelled'],
                'approved' => ['completed'],
            ];
            $this->assertAllowed($from, $to, $allowed);

            $manager = $this->assignments->isManager($actor);
            $member = $campaign->members()->where('users.id', $actor->id)->exists();

            if ($to === 'assigned') {
                abort_unless($manager, 403);
                $this->requireFields($campaign, ['pic_id', 'deadline']);
                if (! $campaign->members()->exists()) {
                    throw ValidationException::withMessages(['member_ids' => 'At least one Tim member is required before assignment.']);
                }
            } elseif (in_array($to, ['in_progress', 'waiting_review'], true)) {
                abort_unless($manager || $member, 403);
            } else {
                abort_unless($manager, 403);
            }

            if ($to === 'approved') {
                abort_unless($actor->can('campaign.approve'), 403);
            }
            if (in_array($to, ['revision', 'cancelled'], true)) {
                $this->requireNote($note);
            }

            $updates = ['status' => $to];
            if (in_array($to, ['revision', 'approved'], true)) {
                $updates['approval_notes'] = $note;
            }
            if ($to === 'completed') {
                $updates['completed_at'] = now();
            }

            $campaign->update($updates);
            $this->logTransition($campaign, $actor, $from, $to, $note);

            return $campaign->refresh();
        });
    }

    public function task(Task $task, User $actor, string $target, ?string $note = null): Task
    {
        return DB::transaction(function () use ($task, $actor, $target, $note): Task {
            $task = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $from = $this->canonicalTaskStatus($task->progress_status);
            $to = TaskStatus::tryFrom($target)?->value;
            $this->assertTarget($to, 'task');

            $allowed = [
                'pending' => ['assigned', 'cancelled'],
                'assigned' => ['in_progress', 'cancelled'],
                'in_progress' => ['on_hold', 'waiting_review', 'cancelled'],
                'on_hold' => ['in_progress', 'cancelled'],
                'waiting_review' => ['revision', 'completed', 'cancelled'],
                'revision' => ['in_progress', 'cancelled'],
            ];
            $this->assertAllowed($from, $to, $allowed);

            $manager = $this->assignments->isManager($actor);
            $assignee = $task->assignee_id === $actor->id;

            if ($to === 'assigned') {
                abort_unless($manager, 403);
                $this->requireFields($task, ['brand_id', 'pic_id', 'assignee_id', 'description', 'deadline']);
            } elseif (in_array($to, ['in_progress', 'on_hold', 'waiting_review'], true)) {
                abort_unless($manager || $assignee, 403);
            } else {
                abort_unless($manager, 403);
            }

            if ($to === 'on_hold') {
                $this->requireNote($note);
            }
            if ($to === 'waiting_review') {
                $this->requireFields($task, ['completion_summary']);
                if ($task->requires_visual && ! $task->visual_link && ! $task->visual_file_path) {
                    throw ValidationException::withMessages(['evidence' => 'Visual evidence is required before review.']);
                }
            }
            if ($to === 'revision') {
                $this->requireNote($note);
            }
            if ($to === 'completed') {
                abort_unless($actor->can('task.review'), 403);
            }
            if ($to === 'cancelled') {
                $this->requireNote($note);
            }

            $updates = ['progress_status' => $to];
            if ($to === 'on_hold') {
                $updates['hold_reason'] = $note;
            }
            if ($to === 'revision') {
                $updates['revision_notes'] = $note;
            }
            if ($to === 'completed') {
                $updates['completed_at'] = now();
            }

            $task->update($updates);
            $this->reminders->schedule($task, $to === 'assigned' || $to === 'revision');
            $this->logTransition($task, $actor, $from, $to, $note);
            if ($to === 'revision' && $task->assignee_id !== null) {
                $this->notifications->send(
                    'in_app',
                    $task->assignee_id,
                    "Task '{$task->name}' memerlukan revisi: {$note}",
                    [
                        'subject' => 'Revision Request',
                        'related_entity' => Task::class,
                        'related_entity_id' => $task->id,
                        'event' => 'task.revision.requested',
                    ]
                );
            }

            return $task->refresh();
        });
    }

    public function report(PerformanceReport $report, User $actor, string $target, ?string $note = null): PerformanceReport
    {
        return DB::transaction(function () use ($report, $actor, $target, $note): PerformanceReport {
            $report = PerformanceReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $from = $report->status;
            $to = PerformanceReportStatus::tryFrom($target)?->value;
            $this->assertTarget($to, 'report');

            $allowed = [
                'draft' => ['waiting_review'],
                'waiting_review' => ['revision', 'approved'],
                'revision' => ['waiting_review'],
                'approved' => ['published'],
            ];
            $this->assertAllowed($from, $to, $allowed);

            $manager = $this->assignments->isManager($actor);
            $author = $report->author_id === $actor->id;

            if ($to === 'waiting_review') {
                abort_unless($manager || $author, 403);
                $this->requireFields($report, ['executive_summary', 'content']);
            } else {
                abort_unless($manager, 403);
            }

            if ($to === 'revision') {
                abort_unless($actor->can('performance-report.review'), 403);
                $this->requireNote($note);
            }
            if ($to === 'approved') {
                abort_unless($actor->can('performance-report.review'), 403);
            }
            if ($to === 'published') {
                abort_unless($actor->can('performance-report.publish'), 403);
            }

            $updates = ['status' => $to];
            if ($to === 'revision') {
                $updates['review_notes'] = $note;
            }
            if ($to === 'approved') {
                $updates['approved_at'] = now();
            }
            if ($to === 'published') {
                $updates['published_at'] = now();
            }

            $report->update($updates);
            $this->logTransition($report, $actor, $from, $to, $note);
            if ($to === 'revision') {
                $this->notifications->send(
                    'in_app',
                    $report->author_id,
                    "Performance Report '{$report->title}' memerlukan revisi: {$note}",
                    [
                        'subject' => 'Report Revision Request',
                        'related_entity' => PerformanceReport::class,
                        'related_entity_id' => $report->id,
                        'event' => 'performance-report.revision.requested',
                    ]
                );
            }

            return $report->refresh();
        });
    }

    /** @param array<string, array<int, string>> $allowed */
    private function assertAllowed(string $from, string $to, array $allowed): void
    {
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "Transition from {$from} to {$to} is not allowed."]);
        }
    }

    private function assertTarget(?string $target, string $workflow): void
    {
        if ($target === null) {
            throw ValidationException::withMessages(['status' => "Unknown {$workflow} status."]);
        }
    }

    private function requireNote(?string $note): void
    {
        if (trim((string) $note) === '') {
            throw ValidationException::withMessages(['note' => 'A note is required for this transition.']);
        }
    }

    /** @param array<int, string> $fields */
    private function requireFields(Model $model, array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if ($model->getAttribute($field) === null || $model->getAttribute($field) === '') {
                $missing[$field] = "The {$field} field is required before this transition.";
            }
        }
        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }
    }

    private function logTransition(Model $model, User $actor, string $from, string $to, ?string $note): void
    {
        ActivityLogger::log(
            action: 'Status Changed',
            description: class_basename($model)." status changed from {$from} to {$to}.",
            actorType: 'Admin',
            actorName: $actor->name,
            loggable: $model,
            actorId: $actor->id,
            properties: [
                'old_status' => $from,
                'new_status' => $to,
                'note' => $note,
                'transitioned_at' => now()->toISOString(),
            ]
        );
    }

    private function canonicalCampaignStatus(string $status): string
    {
        return [
            'Draft' => 'draft', 'Waiting Approval' => 'waiting_review', 'Approved' => 'approved',
            'Running' => 'in_progress', 'Finished' => 'completed', 'Completed' => 'completed',
            'Cancelled' => 'cancelled',
        ][$status] ?? $status;
    }

    private function canonicalTaskStatus(string $status): string
    {
        return [
            'NotStarted' => 'pending', 'Not Started' => 'pending', 'InProgress' => 'in_progress',
            'In Progress' => 'in_progress', 'OnHold' => 'on_hold', 'On Hold' => 'on_hold',
            'Revision' => 'revision', 'Completed' => 'completed',
        ][$status] ?? $status;
    }
}
