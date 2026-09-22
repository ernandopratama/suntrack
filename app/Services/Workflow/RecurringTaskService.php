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

        if ($task->recurrence_max_occurrences !== null
            && $task->recurrence_generated_count >= $task->recurrence_max_occurrences) {
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
            'daily' => $this->atConfiguredTime(
                $task,
                $from->copy()->addDays(max(1, $task->recurrence_interval ?? 1))
            ),
            'weekly' => $this->nextWeeklyOccurrence($task, $from),
            'monthly' => $this->nextMonthlyOccurrence($task, $from),
            default => $from->copy(),
        };
    }

    private function nextWeeklyOccurrence(Task $task, CarbonInterface $from): CarbonInterface
    {
        $weekdays = collect($task->recurrence_weekdays ?: [$from->dayOfWeekIso])
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values();

        $laterDay = $weekdays->first(fn (int $day) => $day > $from->dayOfWeekIso);
        if ($laterDay !== null) {
            return $this->atConfiguredTime(
                $task,
                $from->copy()->startOfWeek()->addDays($laterDay - 1)
            );
        }

        $nextDay = $weekdays->first() ?? $from->dayOfWeekIso;

        return $this->atConfiguredTime(
            $task,
            $from->copy()
                ->startOfWeek()
                ->addWeeks(max(1, $task->recurrence_interval ?? 1))
                ->addDays($nextDay - 1)
        );
    }

    private function nextMonthlyOccurrence(Task $task, CarbonInterface $from): CarbonInterface
    {
        $month = $from->copy()
            ->startOfMonth()
            ->addMonthsNoOverflow(max(1, $task->recurrence_interval ?? 1));
        $configuredDay = $task->recurrence_month_day;
        $day = $configuredDay === 'last'
            ? $month->daysInMonth
            : min(max(1, (int) ($configuredDay ?: $from->day)), $month->daysInMonth);

        return $this->atConfiguredTime($task, $month->day($day));
    }

    private function atConfiguredTime(Task $task, CarbonInterface $date): CarbonInterface
    {
        $time = $task->recurrence_time ?: $task->deadline?->format('H:i') ?: '09:00';
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $date->copy()->setTime($hour, $minute, 0);
    }
}
