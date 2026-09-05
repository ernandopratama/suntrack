<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\Notification\NotificationService;
use App\Services\Workflow\TaskReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendTaskPriorityReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function handle(NotificationService $notifications, TaskReminderService $reminders): void
    {
        Task::query()
            ->whereIn('progress_status', ['assigned', 'in_progress', 'revision', 'on_hold'])
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now())
            ->orderBy('next_reminder_at')
            ->pluck('id')
            ->each(function (string $taskId) use ($notifications, $reminders): void {
                $task = DB::transaction(function () use ($taskId, $reminders): ?Task {
                    $task = Task::query()->with(['brand', 'assignee', 'pic'])->whereKey($taskId)->lockForUpdate()->first();
                    if ($task === null || $task->next_reminder_at === null || $task->next_reminder_at->isFuture()) {
                        return null;
                    }
                    $task->forceFill([
                        'last_reminded_at' => now(),
                        'next_reminder_at' => $reminders->nextAt($task),
                        'reminder_count' => $task->reminder_count + 1,
                    ])->save();

                    return $task;
                });
                if ($task === null) {
                    return;
                }

                $title = $task->progress_status === 'on_hold'
                    ? "Evaluasi Task On Hold: {$task->name}"
                    : "Reminder Task {$task->priority}: {$task->name}";
                $message = implode(' | ', [
                    $task->name,
                    'Brand: '.$task->brand->name,
                    'Priority: '.strtoupper($task->priority),
                    'Deadline: '.($task->deadline?->format('Y-m-d H:i') ?? '-'),
                    'Status: '.str_replace('_', ' ', $task->progress_status),
                    url("/tasks?task={$task->id}"),
                ]);

                collect([$task->assignee_id, $task->pic_id])->filter()->unique()->each(
                    fn (string $recipient) => $notifications->sendReminder(
                        'in_app',
                        $recipient,
                        $title,
                        $message,
                        Task::class,
                        $task->id
                    )
                );

                ActivityLogger::log(
                    'Task Priority Reminder',
                    "Reminder #{$task->reminder_count} sent for Task '{$task->name}'.",
                    'System',
                    'LaravelScheduler',
                    $task,
                    properties: ['priority' => $task->priority, 'next_reminder_at' => $task->next_reminder_at?->toIso8601String()]
                );
            });
    }
}
