<?php

namespace App\Services\Workflow;

use App\Models\Task;
use Carbon\CarbonInterface;

class RecurringTaskService
{
    public const TYPES = ['daily', 'weekly', 'monthly'];

    public function schedule(Task $task): void
    {
        if (! in_array($task->recurrence_type, self::TYPES, true) || $task->deadline === null) {
            $task->forceFill(['next_recurrence_at' => null])->save();

            return;
        }

        $nextOccurrence = $this->nextOccurrenceAt($task, $task->deadline);
        $task->forceFill([
            'next_recurrence_at' => $task->recurrence_ends_at !== null
                && $nextOccurrence->isAfter($task->recurrence_ends_at)
                    ? null
                    : $task->deadline,
        ])->save();
    }

    public function nextOccurrenceAt(Task $task, CarbonInterface $from): CarbonInterface
    {
        return match ($task->recurrence_type) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonthNoOverflow(),
            default => $from->copy(),
        };
    }
}
