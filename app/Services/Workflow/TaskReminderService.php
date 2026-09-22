<?php

namespace App\Services\Workflow;

use App\Models\Task;
use Carbon\CarbonInterface;

class TaskReminderService
{
    private const ACTIONABLE_STATUSES = ['assigned', 'in_progress', 'revision', 'on_hold'];

    public function schedule(Task $task, bool $immediate = false): void
    {
        if (! in_array($task->progress_status, self::ACTIONABLE_STATUSES, true) || $task->deadline === null) {
            $task->forceFill(['next_reminder_at' => null])->save();

            return;
        }

        $task->forceFill([
            'next_reminder_at' => $immediate
                ? now()
                : $this->nextAt($task, now()->subMinute()),
        ])->save();
    }

    public function nextAt(Task $task, ?CarbonInterface $from = null): ?CarbonInterface
    {
        if ($task->deadline === null) {
            return null;
        }

        $from ??= now();
        $recurrenceType = $task->recurrence_type
            ?: $task->recurrenceSource()->value('recurrence_type');
        $offsets = $recurrenceType === 'daily'
            ? [1440, 60]
            : [4320, 1440];

        foreach ($offsets as $minutesBeforeDeadline) {
            $candidate = $task->deadline->copy()->subMinutes($minutesBeforeDeadline);
            if ($candidate->greaterThan($from)) {
                return $candidate;
            }
        }

        return null;
    }
}
