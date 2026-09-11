<?php

namespace App\Services\Workflow;

use App\Models\Campaign;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;

class CampaignDeadlineService
{
    public const DEFAULT_APPROACHING_DAYS = 7;

    private const TERMINAL_STATUSES = ['completed', 'cancelled'];

    public function __construct(private SettingsService $settings) {}

    public function approachingDays(): int
    {
        return min(365, max(1, (int) $this->settings->get(
            'campaign_approaching_deadline_days',
            self::DEFAULT_APPROACHING_DAYS
        )));
    }

    /**
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function applyMonitoringFilter(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            'active' => $query->whereNotIn('status', self::TERMINAL_STATUSES),
            'completed' => $query->where('status', 'completed'),
            'approaching_deadline' => $query
                ->whereNotIn('status', self::TERMINAL_STATUSES)
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [now(), now()->addDays($this->approachingDays())]),
            'overdue' => $query
                ->whereNotIn('status', self::TERMINAL_STATUSES)
                ->whereNotNull('deadline')
                ->where('deadline', '<', now()),
            'waiting_review' => $query->where('status', 'waiting_review'),
            'revision' => $query->where('status', 'revision'),
            default => $query,
        };
    }

    public function state(Campaign $campaign): ?string
    {
        if ($campaign->deadline === null || in_array($campaign->status, self::TERMINAL_STATUSES, true)) {
            return null;
        }

        if ($campaign->deadline->isPast()) {
            return 'overdue';
        }

        return $campaign->deadline->lte(now()->addDays($this->approachingDays()))
            ? 'approaching_deadline'
            : null;
    }
}
