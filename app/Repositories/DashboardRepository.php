<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Models\ApprovalHistory;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SecureLink;
use App\Models\Variant;
use App\Services\Cache\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Retrieve aggregated KPI metrics across all modules with Redis tag caching (ADR-021/Sprint 10).
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(string $todayStr): array
    {
        return $this->cache->remember(['dashboard', 'kpi'], "dashboard_kpi_{$todayStr}", 300, function () use ($todayStr) {
            $campaignStats = [
                'total'     => Campaign::count(),
                'active'    => Campaign::where('status', 'Running')->count(),
                'completed' => Campaign::where('status', 'Completed')->count(),
            ];

            $promotionStats = [
                'total'             => Promotion::count(),
                'active'            => Promotion::where('status', 'Approved')->count(),
                'pending'           => Promotion::where('status', 'Pending')->count(),
                'approved'          => Promotion::where('status', 'Approved')->count(),
                'partially_approved'=> Promotion::where('status', 'Partially Approved')->count(),
                'rejected'          => Promotion::where('status', 'Rejected')->count(),
            ];

            $catalogStats = [
                'total_products'     => Product::count(),
                'total_variants'     => Variant::count(),
                'total_secure_links' => SecureLink::count(),
                'total_brand_reviews'=> ApprovalHistory::count(),
            ];

            $totalDecisions = ApprovalHistory::count();
            $approvedDecisions = ApprovalHistory::where('new_status', 'Approved')->count();
            $approvalRate = $totalDecisions > 0 ? round(($approvedDecisions / $totalDecisions) * 100, 1) : 0.0;

            $extensibleKpis = [
                'approval_rate'         => $approvalRate,
                'total_comments'        => Comment::count(),
                'total_activity_today'  => ActivityLog::whereDate('created_at', $todayStr)->count(),
            ];

            return [
                'campaigns'  => $campaignStats,
                'promotions' => $promotionStats,
                'catalog'    => $catalogStats,
                'extended'   => $extensibleKpis,
            ];
        });
    }

    /**
     * Retrieve deadline monitoring datasets with Redis tag caching.
     *
     * @return array<string, mixed>
     */
    public function getDeadlines(string $todayStr, string $tomorrowStr, string $next7DaysStr, Carbon $now): array
    {
        return $this->cache->remember(['dashboard', 'deadlines'], "dashboard_deadlines_{$todayStr}", 300, function () use ($todayStr, $tomorrowStr, $next7DaysStr, $now) {
            return [
                'today'          => $this->getDeadlineItems($todayStr, $todayStr, 'today'),
                'tomorrow'       => $this->getDeadlineItems($tomorrowStr, $tomorrowStr, 'tomorrow'),
                'next_7_days'    => $this->getDeadlineItems($todayStr, $next7DaysStr, '7_days'),
                'overdue'        => $this->getOverdueCampaigns($todayStr),
                'expiring_links' => $this->getExpiringLinks($now),
            ];
        });
    }

    /**
     * Retrieve recent system activity logs with eager loaded relationships.
     */
    public function getRecentActivities(int $limit = 15): Collection
    {
        return ActivityLog::with(['actor', 'loggable'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Helper to fetch and format deadline monitoring items for a date range.
     */
    protected function getDeadlineItems(string $startStr, string $endStr, string $category): Collection
    {
        $campaigns = Campaign::with('brand')
            ->whereBetween('end_date', [$startStr . ' 00:00:00', $endStr . ' 23:59:59'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($c) use ($category) {
                return [
                    'id'          => $c->id,
                    'type'        => 'Campaign',
                    'title'       => $c->name,
                    'subtitle'    => $c->brand?->name ?? 'Standalone',
                    'deadline'    => $c->end_date->format('Y-m-d'),
                    'status'      => $c->status,
                    'status_code' => $category === 'today' ? 'yellow' : 'green',
                    'url'         => "/campaigns/{$c->id}",
                ];
            });

        $promotions = Promotion::with(['brand', 'campaign'])
            ->whereBetween('end_date', [$startStr . ' 00:00:00', $endStr . ' 23:59:59'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($p) use ($category) {
                return [
                    'id'          => $p->id,
                    'type'        => 'Promotion',
                    'title'       => $p->code . ' - ' . $p->name,
                    'subtitle'    => $p->brand?->name ?? ($p->campaign?->name ?? 'Standalone'),
                    'deadline'    => $p->end_date->format('Y-m-d'),
                    'status'      => $p->status,
                    'status_code' => $category === 'today' ? 'yellow' : 'green',
                    'url'         => "/promotions/{$p->id}",
                ];
            });

        return $campaigns->concat($promotions)->values();
    }

    protected function getOverdueCampaigns(string $todayStr): Collection
    {
        return Campaign::with('brand')
            ->whereDate('end_date', '<', $todayStr)
            ->whereNotIn('status', ['Completed', 'Archived', 'Cancelled'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'type'        => 'Campaign',
                'title'       => $c->name,
                'subtitle'    => $c->brand?->name ?? 'Standalone',
                'deadline'    => $c->end_date->format('Y-m-d'),
                'status'      => $c->status,
                'status_code' => 'red',
                'url'         => "/campaigns/{$c->id}",
            ]);
    }

    protected function getExpiringLinks(Carbon $now): Collection
    {
        return SecureLink::with('linkable')
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now, $now->copy()->addDays(7)])
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(fn($l) => [
                'id'          => $l->id,
                'type'        => 'Secure Link (' . class_basename($l->linkable_type) . ')',
                'title'       => $l->linkable?->name ?? 'Public Review Link',
                'subtitle'    => 'Expires in ' . $l->expires_at->diffForHumans(),
                'deadline'    => $l->expires_at->format('Y-m-d H:i'),
                'status'      => 'Expiring Soon',
                'status_code' => 'yellow',
                'url'         => $l->linkable_type === Promotion::class ? "/promotions/{$l->linkable_id}" : "/campaigns/{$l->linkable_id}",
            ]);
    }
}
