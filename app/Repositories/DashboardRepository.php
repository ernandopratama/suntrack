<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Models\ApprovalHistory;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SecureLink;
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
            $approvalHistories = $this->scoped(ApprovalHistory::query(), $user);
            $comments = $this->scoped(Comment::query(), $user);
            $activityLogs = $this->scoped(ActivityLog::query(), $user);

            $campaignStats = [
                'total' => (clone $campaigns)->count(),
                'active' => (clone $campaigns)->where('status', 'Running')->count(),
                'completed' => (clone $campaigns)->where('status', 'Completed')->count(),
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

        return $campaigns->concat($promotions)->values();
    }

    protected function getOverdueCampaigns(string $todayStr, ?User $user = null): Collection
    {
        return $this->scoped(Campaign::with('brand'), $user)
            ->whereDate('end_date', '<', $todayStr)
            ->whereNotIn('status', ['Completed', 'Archived', 'Cancelled'])
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

        return $linkable instanceof Campaign || $linkable instanceof Promotion
            ? $linkable->name
            : 'Public Review Link';
    }
}
