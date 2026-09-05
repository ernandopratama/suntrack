<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Models\ApprovalHistory;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\PerformanceReport;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SecureLink;
use App\Models\Task;
use App\Models\User;
use App\Models\Variant;
use App\Services\Authorization\DataScopeService;
use App\Services\Cache\CacheService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function __construct(
        protected CacheService $cache = new CacheService,
        protected DataScopeService $dataScope = new DataScopeService
    ) {}

    /**
     * Retrieve aggregated KPI metrics across all modules with Redis tag caching (ADR-021/Sprint 10).
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(string $todayStr, ?User $user = null): array
    {
        $scopeKey = $this->scopeKey($user);

        return $this->cache->remember(['dashboard', 'kpi'], "dashboard_kpi_{$scopeKey}_{$todayStr}", 300, function () use ($todayStr, $user) {
            $campaigns = $this->scoped(Campaign::query(), $user);
            $promotions = $this->scoped(Promotion::query(), $user);
            $products = $this->scoped(Product::query(), $user);
            $variants = $this->scoped(Variant::query(), $user);
            $secureLinks = $this->scoped(SecureLink::query(), $user);
            $tasks = $this->scoped(Task::query(), $user);
            $reports = $this->scoped(PerformanceReport::query(), $user);
            $approvalHistories = $this->scoped(ApprovalHistory::query(), $user);
            $comments = $this->scoped(Comment::query(), $user);
            $activityLogs = $this->scoped(ActivityLog::query(), $user);

            $campaignStats = [
                'total' => (clone $campaigns)->count(),
                'active' => (clone $campaigns)->whereIn('status', ['assigned', 'in_progress', 'Running'])->count(),
                'completed' => (clone $campaigns)->whereIn('status', ['completed', 'Finished', 'Completed'])->count(),
            ];

            $promotionStats = [
                'total' => (clone $promotions)->count(),
                'active' => (clone $promotions)->where('status', 'Approved')->count(),
                'pending' => (clone $promotions)->where('status', 'Pending')->count(),
                'approved' => (clone $promotions)->where('status', 'Approved')->count(),
                'partially_approved' => (clone $promotions)->where('status', 'Partially Approved')->count(),
                'rejected' => (clone $promotions)->where('status', 'Rejected')->count(),
            ];

            $catalogStats = [
                'total_products' => (clone $products)->count(),
                'total_variants' => (clone $variants)->count(),
                'total_secure_links' => (clone $secureLinks)->count(),
                'total_brand_reviews' => (clone $approvalHistories)->count(),
            ];

            $taskStats = [
                'total' => (clone $tasks)->count(),
                'open' => (clone $tasks)->whereNotIn('progress_status', ['completed', 'cancelled'])->count(),
                'urgent' => (clone $tasks)->where('priority', 'urgent')->whereNotIn('progress_status', ['completed', 'cancelled'])->count(),
                'waiting_review' => (clone $tasks)->where('progress_status', 'waiting_review')->count(),
                'overdue' => (clone $tasks)->whereNotIn('progress_status', ['completed', 'cancelled'])
                    ->whereNotNull('deadline')->where('deadline', '<', now())->count(),
                'completed' => (clone $tasks)->where('progress_status', 'completed')->count(),
            ];

            $reportStats = [
                'total' => (clone $reports)->count(),
                'draft' => (clone $reports)->where('status', 'draft')->count(),
                'waiting_review' => (clone $reports)->where('status', 'waiting_review')->count(),
                'approved' => (clone $reports)->where('status', 'approved')->count(),
                'published' => (clone $reports)->where('status', 'published')->count(),
            ];

            $totalDecisions = (clone $approvalHistories)->count();
            $approvedDecisions = (clone $approvalHistories)->where('new_status', 'Approved')->count();
            $approvalRate = $totalDecisions > 0 ? round(($approvedDecisions / $totalDecisions) * 100, 1) : 0.0;

            $extensibleKpis = [
                'approval_rate' => $approvalRate,
                'total_comments' => (clone $comments)->count(),
                'total_activity_today' => (clone $activityLogs)->whereDate('created_at', $todayStr)->count(),
            ];

            return [
                'campaigns' => $campaignStats,
                'promotions' => $promotionStats,
                'catalog' => $catalogStats,
                'tasks' => $taskStats,
                'performance_reports' => $reportStats,
                'extended' => $extensibleKpis,
            ];
        });
    }

    /**
     * Retrieve deadline monitoring datasets with Redis tag caching.
     *
     * @return array<string, mixed>
     */
    public function getDeadlines(string $todayStr, string $tomorrowStr, string $next7DaysStr, Carbon $now, ?User $user = null): array
    {
        $scopeKey = $this->scopeKey($user);

        return $this->cache->remember(['dashboard', 'deadlines'], "dashboard_deadlines_{$scopeKey}_{$todayStr}", 300, function () use ($todayStr, $tomorrowStr, $next7DaysStr, $now, $user) {
            return [
                'today' => $this->getDeadlineItems($todayStr, $todayStr, 'today', $user),
                'tomorrow' => $this->getDeadlineItems($tomorrowStr, $tomorrowStr, 'tomorrow', $user),
                'next_7_days' => $this->getDeadlineItems($todayStr, $next7DaysStr, '7_days', $user),
                'overdue' => $this->getOverdueCampaigns($todayStr, $user),
                'expiring_links' => $this->getExpiringLinks($now, $user),
            ];
        });
    }

    /**
     * Retrieve recent system activity logs with eager loaded relationships.
     */
    public function getRecentActivities(int $limit = 15, ?User $user = null): Collection
    {
        return $this->scoped(ActivityLog::with(['actor', 'loggable']), $user)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Helper to fetch and format deadline monitoring items for a date range.
     */
    protected function getDeadlineItems(string $startStr, string $endStr, string $category, ?User $user = null): Collection
    {
        $campaigns = $this->scoped(Campaign::with('brand'), $user)
            ->whereBetween('end_date', [$startStr.' 00:00:00', $endStr.' 23:59:59'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($c) use ($category) {
                return [
                    'id' => $c->id,
                    'type' => 'Campaign',
                    'title' => $c->name,
                    'subtitle' => $c->brand->name ?? 'Standalone',
                    'deadline' => $c->end_date->format('Y-m-d'),
                    'status' => $c->status,
                    'status_code' => $category === 'today' ? 'yellow' : 'green',
                    'url' => "/campaigns/{$c->id}",
                ];
            });

        $tasks = $this->scoped(Task::with('brand'), $user)
            ->whereNotIn('progress_status', ['completed', 'cancelled'])
            ->whereBetween('deadline', [$startStr.' 00:00:00', $endStr.' 23:59:59'])
            ->orderBy('deadline')
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'type' => 'Task',
                'title' => $task->name,
                'subtitle' => $task->brand->name,
                'deadline' => $task->deadline?->format('Y-m-d H:i'),
                'status' => $task->progress_status,
                'status_code' => $task->priority === 'urgent' ? 'red' : ($category === 'today' ? 'yellow' : 'green'),
                'url' => "/tasks?task={$task->id}",
            ]);

        $promotions = $this->scoped(Promotion::with(['brand', 'campaign']), $user)
            ->whereBetween('end_date', [$startStr.' 00:00:00', $endStr.' 23:59:59'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($p) use ($category) {
                return [
                    'id' => $p->id,
                    'type' => 'Promotion',
                    'title' => $p->code.' - '.$p->name,
                    'subtitle' => $p->brand->name ?? ($p->campaign->name ?? 'Standalone'),
                    'deadline' => $p->end_date->format('Y-m-d'),
                    'status' => $p->status,
                    'status_code' => $category === 'today' ? 'yellow' : 'green',
                    'url' => "/promotions/{$p->id}",
                ];
            });

        return $campaigns->concat($promotions)->concat($tasks)->sortBy('deadline')->values();
    }

    protected function getOverdueCampaigns(string $todayStr, ?User $user = null): Collection
    {
        $campaigns = $this->scoped(Campaign::with('brand'), $user)
            ->whereDate('end_date', '<', $todayStr)
            ->whereNotIn('status', ['completed', 'cancelled', 'Completed', 'Finished', 'Archived', 'Cancelled'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'type' => 'Campaign',
                'title' => $c->name,
                'subtitle' => $c->brand->name ?? 'Standalone',
                'deadline' => $c->end_date->format('Y-m-d'),
                'status' => $c->status,
                'status_code' => 'red',
                'url' => "/campaigns/{$c->id}",
            ]);

        $tasks = $this->scoped(Task::with('brand'), $user)
            ->whereNotIn('progress_status', ['completed', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $todayStr)
            ->orderBy('deadline')
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'type' => 'Task',
                'title' => $task->name,
                'subtitle' => $task->brand->name,
                'deadline' => $task->deadline?->format('Y-m-d H:i'),
                'status' => $task->progress_status,
                'status_code' => 'red',
                'url' => "/tasks?task={$task->id}",
            ]);

        return $campaigns->concat($tasks)->sortBy('deadline')->values();
    }

    protected function getExpiringLinks(Carbon $now, ?User $user = null): Collection
    {
        return $this->scoped(SecureLink::with('linkable'), $user)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now, $now->copy()->addDays(7)])
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'type' => 'Secure Link ('.class_basename($l->linkable_type).')',
                'title' => $this->secureLinkTitle($l),
                'subtitle' => 'Expires in '.$l->expires_at->diffForHumans(),
                'deadline' => $l->expires_at->format('Y-m-d H:i'),
                'status' => 'Expiring Soon',
                'status_code' => 'yellow',
                'url' => $l->linkable_type === Promotion::class ? "/promotions/{$l->linkable_id}" : "/campaigns/{$l->linkable_id}",
            ]);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function scoped(Builder $query, ?User $user): Builder
    {
        return $user === null ? $query : $this->dataScope->scope($query, $user);
    }

    private function scopeKey(?User $user): string
    {
        if ($user === null || $this->dataScope->hasGlobalScope($user)) {
            return 'global';
        }

        return 'user_'.$user->id;
    }

    private function secureLinkTitle(SecureLink $link): string
    {
        $linkable = $link->linkable;

        if ($linkable instanceof PerformanceReport) {
            return $linkable->title;
        }

        return $linkable instanceof Campaign || $linkable instanceof Promotion || $linkable instanceof Task
            ? $linkable->name
            : 'Public Review Link';
    }
}
