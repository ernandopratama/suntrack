<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\Notification\NotificationService;
use App\Services\Workflow\RecurringTaskService;
use App\Services\Workflow\TaskReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateRecurringTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function handle(
        RecurringTaskService $recurringTasks,
        TaskReminderService $reminders,
        NotificationService $notifications
    ): void {
        Task::query()
            ->whereIn('recurrence_type', RecurringTaskService::TYPES)
            ->whereNotNull('next_recurrence_at')
            ->where('next_recurrence_at', '<=', now())
            ->orderBy('next_recurrence_at')
            ->pluck('id')
            ->each(function (string $sourceId) use ($recurringTasks, $reminders, $notifications): void {
                $result = DB::transaction(function () use ($sourceId, $recurringTasks): ?array {
                    $source = Task::query()->whereKey($sourceId)->lockForUpdate()->first();
                    if ($source === null
                        || $source->next_recurrence_at === null
                        || $source->next_recurrence_at->isFuture()
                        || ! in_array($source->recurrence_type, RecurringTaskService::TYPES, true)) {
                        return null;
                    }

                    if ($source->recurrence_max_occurrences !== null
                        && $source->recurrence_generated_count >= $source->recurrence_max_occurrences) {
                        $source->forceFill(['next_recurrence_at' => null])->save();

                        return null;
                    }

                    $occurrenceAt = $recurringTasks->nextOccurrenceAt($source, $source->next_recurrence_at);
                    if ($source->recurrence_ends_at !== null && $occurrenceAt->isAfter($source->recurrence_ends_at)) {
                        $source->forceFill(['next_recurrence_at' => null])->save();

                        return null;
                    }

                    $occurrence = Task::query()->firstOrCreate(
                        [
                            'recurrence_source_id' => $source->id,
                            'recurrence_occurrence_at' => $occurrenceAt,
                        ],
                        [
                            'campaign_id' => $source->campaign_id,
                            'brand_id' => $source->brand_id,
                            'created_by' => $source->created_by,
                            'is_personal' => $source->is_personal,
                            'pic_id' => $source->pic_id,
                            'assignee_id' => $source->assignee_id,
                            'name' => $source->name,
                            'description' => $source->description,
                            'progress_status' => $source->assignee_id !== null ? 'assigned' : 'pending',
                            'priority' => $source->priority,
                            'notes' => $source->notes,
                            'requires_visual' => $source->requires_visual,
                            'visual_type' => $source->visual_type,
                            'creative_brief' => $source->creative_brief,
                            'deadline' => $occurrenceAt,
                            'recurrence_notify' => false,
                        ]
                    );

                    $generatedCount = $source->recurrence_generated_count + ($occurrence->wasRecentlyCreated ? 1 : 0);
                    $followingOccurrence = $recurringTasks->nextOccurrenceAt($source, $occurrenceAt);
                    $reachedOccurrenceLimit = $source->recurrence_max_occurrences !== null
                        && $generatedCount >= $source->recurrence_max_occurrences;
                    $source->forceFill([
                        'recurrence_generated_count' => $generatedCount,
                        'next_recurrence_at' => $reachedOccurrenceLimit
                            || ($source->recurrence_ends_at !== null
                            && $followingOccurrence->isAfter($source->recurrence_ends_at))
                                ? null
                                : $occurrenceAt,
                    ])->save();

                    return [
                        'task' => $occurrence,
                        'created' => $occurrence->wasRecentlyCreated,
                        'notify' => $source->recurrence_notify,
                    ];
                });

                if ($result === null || ! $result['created']) {
                    return;
                }

                /** @var Task $occurrence */
                $occurrence = $result['task'];
                $reminders->schedule($occurrence);

                if ($result['notify']) {
                    collect([$occurrence->created_by, $occurrence->pic_id, $occurrence->assignee_id])
                        ->filter()
                        ->unique()
                        ->each(fn (string $recipient) => $notifications->send(
                            'in_app',
                            $recipient,
                            "Task '{$occurrence->name}' untuk jadwal ".$occurrence->deadline?->format('d-m-Y H:i').' telah dibuat.',
                            [
                                'subject' => 'Task Berulang Telah Dibuat',
                                'related_entity' => Task::class,
                                'related_entity_id' => $occurrence->id,
                                'notification_type' => 'recurring_task_created',
                            ]
                        ));
                }

                ActivityLogger::log(
                    action: 'Task Berulang Dibuat',
                    description: "Task berulang '{$occurrence->name}' telah dibuat untuk jadwal berikutnya.",
                    actorType: 'System',
                    actorName: 'LaravelScheduler',
                    loggable: $occurrence,
                    properties: [
                        'recurrence_source_id' => $occurrence->recurrence_source_id,
                        'recurrence_occurrence_at' => $occurrence->recurrence_occurrence_at?->toIso8601String(),
                    ]
                );
            });
    }
}
