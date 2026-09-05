<?php

namespace App\Services\Workflow;

use App\Models\Task;
use App\Services\Settings\SettingsService;
use Carbon\CarbonInterface;

class TaskReminderService
{
    private const ACTIONABLE_STATUSES = ['assigned', 'in_progress', 'revision', 'on_hold'];

    public function __construct(private SettingsService $settings) {}

    public function schedule(Task $task, bool $immediate = false): void
    {
        if (! in_array($task->progress_status, self::ACTIONABLE_STATUSES, true)) {
            $task->forceFill(['next_reminder_at' => null])->save();

            return;
        }

        $task->forceFill([
            'next_reminder_at' => $immediate ? now() : $this->nextAt($task),
        ])->save();
    }

    public function nextAt(Task $task, ?CarbonInterface $from = null): CarbonInterface
    {
        return ($from ?? now())->copy()->addMinutes($this->intervalMinutes($task));
    }

    public function intervalMinutes(Task $task): int
    {
        $key = $task->progress_status === 'on_hold'
            ? 'task_reminder_on_hold_minutes'
            : 'task_reminder_'.$task->priority.'_minutes';
        $defaults = [
            'task_reminder_normal_minutes' => 1440,
            'task_reminder_mid_minutes' => 480,
            'task_reminder_urgent_minutes' => 120,
            'task_reminder_on_hold_minutes' => 1440,
        ];

        return max(15, (int) $this->settings->get($key, $defaults[$key] ?? 1440));
    }
}
