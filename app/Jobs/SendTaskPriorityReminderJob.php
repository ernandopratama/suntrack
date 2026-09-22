<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\Notification\NotificationService;
use App\Services\Workflow\TaskReminderService;
use Carbon\CarbonInterface;
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
                        'next_reminder_at' => $reminders->nextAt($task, now()),
                        'reminder_count' => $task->reminder_count + 1,
                    ])->save();

                    return $task;
                });
                if ($task === null) {
                    return;
                }

                $title = "Pengingat Task: {$task->name}";
                $remaining = now()->diffForHumans($task->deadline, [
                    'parts' => 2,
                    'short' => false,
                    'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
                ]);
                $message = "Task '{$task->name}' memiliki tenggat {$task->deadline?->format('d-m-Y H:i')} ({$remaining})."
                    .' Prioritas: '.strtoupper($task->priority)
                    .'. Brand: '.($task->brand?->name ?? 'Pribadi').'.';

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
                    'Pengingat Prioritas Task',
                    "Pengingat ke-{$task->reminder_count} dikirim untuk Task '{$task->name}'.",
                    'System',
                    'LaravelScheduler',
                    $task,
                    properties: ['priority' => $task->priority, 'next_reminder_at' => $task->next_reminder_at?->toIso8601String()]
                );
            });
    }
}
