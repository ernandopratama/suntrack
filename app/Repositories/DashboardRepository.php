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
use Illuminate\Support\Facades\Schema;

class DashboardRepository
{
    public function __construct(
        protected CacheService $cache = new CacheService,
        protected DataScopeService $dataScope = new DataScopeService
    ) {}

    /**
     * Retrieve aggregated KPI metrics across all modules
     * with Redis tag caching.
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(
        string $todayStr,
        ?User $user = null
    ): array {
        $scopeKey = $this->scopeKey($user);

        $taskPriorityColumnExists = Schema::hasColumn(
            'tasks',
            'priority'
        );

        return $this->cache->remember(
            ['dashboard', 'kpi'],
            "dashboard_kpi_{$scopeKey}_{$todayStr}",
            300,
            function () use (
                $todayStr,
                $user,
                $taskPriorityColumnExists
            ) {
                $campaigns = $this->scoped(
                    Campaign::query(),
                    $user
                );

                $promotions = $this->scoped(
                    Promotion::query(),
                    $user
                );

                $products = $this->scoped(
                    Product::query(),
                    $user
                );

                $variants = $this->scoped(
                    Variant::query(),
                    $user
                );

                $secureLinks = $this->scoped(
                    SecureLink::query(),
                    $user
                );

                $tasks = $this->scoped(
                    Task::query(),
                    $user
                );

                $reports = $this->scoped(
                    PerformanceReport::query(),
                    $user
                );

                $approvalHistories = $this->scoped(
                    ApprovalHistory::query(),
                    $user
                );

                $comments = $this->scoped(
                    Comment::query(),
                    $user
                );

                $activityLogs = $this->scoped(
                    ActivityLog::query(),
                    $user
                );

                /*
                |--------------------------------------------------------------------------
                | Campaign KPI
                |--------------------------------------------------------------------------
                */

                $campaignStats = [
                    'total' => (clone $campaigns)->count(),

                    'active' => (clone $campaigns)
                        ->whereIn('status', [
                            'assigned',
                            'in_progress',
                            'Running',
                        ])
                        ->count(),

                    'completed' => (clone $campaigns)
                        ->whereIn('status', [
                            'completed',
                            'Finished',
                            'Completed',
                        ])
                        ->count(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Promotion KPI
                |--------------------------------------------------------------------------
                */

                $promotionStats = [
                    'total' => (clone $promotions)->count(),

                    'active' => (clone $promotions)
                        ->where('status', 'Approved')
                        ->count(),

                    'pending' => (clone $promotions)
                        ->where('status', 'Pending')
                        ->count(),

                    'approved' => (clone $promotions)
                        ->where('status', 'Approved')
                        ->count(),

                    'partially_approved' => (clone $promotions)
                        ->where('status', 'Partially Approved')
                        ->count(),

                    'rejected' => (clone $promotions)
                        ->where('status', 'Rejected')
                        ->count(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Catalog KPI
                |--------------------------------------------------------------------------
                */

                $catalogStats = [
                    'total_products' => (clone $products)->count(),

                    'total_variants' => (clone $variants)->count(),

                    'total_secure_links' => (clone $secureLinks)->count(),

                    'total_brand_reviews' => (clone $approvalHistories)
                        ->count(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Task KPI
                |--------------------------------------------------------------------------
                */

                $taskStats = [
                    'total' => (clone $tasks)->count(),

                    'open' => (clone $tasks)
                        ->whereNotIn(
                            'progress_status',
                            ['completed', 'cancelled']
                        )
                        ->count(),

                    'urgent' => $taskPriorityColumnExists
                        ? (clone $tasks)
                        ->where('priority', 'urgent')
                        ->whereNotIn(
                            'progress_status',
                            ['completed', 'cancelled']
                        )
                        ->count()
                        : 0,

                    'waiting_review' => (clone $tasks)
                        ->where('progress_status', 'waiting_review')
                        ->count(),

                    'overdue' => (clone $tasks)
                        ->whereNotIn(
                            'progress_status',
                            ['completed', 'cancelled']
                        )
                        ->whereNotNull('deadline')
                        ->where('deadline', '<', now())
                        ->count(),

                    'completed' => (clone $tasks)
                        ->where('progress_status', 'completed')
                        ->count(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Performance Report KPI
                |--------------------------------------------------------------------------
                */

                $reportStats = [
                    'total' => (clone $reports)->count(),

                    'draft' => (clone $reports)
                        ->where('status', 'draft')
                        ->count(),

                    'waiting_review' => (clone $reports)
                        ->where('status', 'waiting_review')
                        ->count(),

                    'approved' => (clone $reports)
                        ->where('status', 'approved')
                        ->count(),

                    'published' => (clone $reports)
                        ->where('status', 'published')
                        ->count(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Extended KPI
                |--------------------------------------------------------------------------
                */

                $totalDecisions = (clone $approvalHistories)->count();

                $approvedDecisions = (clone $approvalHistories)
                    ->where('new_status', 'Approved')
                    ->count();

                $approvalRate = $totalDecisions > 0
                    ? round(
                        ($approvedDecisions / $totalDecisions) * 100,
                        1
                    )
                    : 0.0;

                $extensibleKpis = [
                    'approval_rate' => $approvalRate,

                    'total_comments' => (clone $comments)->count(),

                    'total_activity_today' => (clone $activityLogs)
                        ->whereDate('created_at', $todayStr)
                        ->count(),
                ];

                return [
                    'campaigns' => $campaignStats,
                    'promotions' => $promotionStats,
                    'catalog' => $catalogStats,
                    'tasks' => $taskStats,
                    'performance_reports' => $reportStats,
                    'extended' => $extensibleKpis,
                ];
            }
        );
    }

    /**
     * Retrieve deadline monitoring datasets
     * with Redis tag caching.
     *
     * @return array<string, mixed>
     */
    public function getDeadlines(
        string $todayStr,
        string $tomorrowStr,
        string $next7DaysStr,
        Carbon $now,
        ?User $user = null
    ): array {
        $scopeKey = $this->scopeKey($user);

        return $this->cache->remember(
            ['dashboard', 'deadlines'],
            "dashboard_deadlines_{$scopeKey}_{$todayStr}",
            300,
            function () use (
                $todayStr,
                $tomorrowStr,
                $next7DaysStr,
                $now,
                $user
            ) {
                return [
                    'today' => $this->getDeadlineItems(
                        $todayStr,
                        $todayStr,
                        'today',
                        $user
                    ),

                    'tomorrow' => $this->getDeadlineItems(
                        $tomorrowStr,
                        $tomorrowStr,
                        'tomorrow',
                        $user
                    ),

                    'next_7_days' => $this->getDeadlineItems(
                        $todayStr,
                        $next7DaysStr,
                        '7_days',
                        $user
                    ),

                    'overdue' => $this->getOverdueCampaigns(
                        $todayStr,
                        $user
                    ),

                    'expiring_links' => $this->getExpiringLinks(
                        $now,
                        $user
                    ),
                ];
            }
        );
    }

    /**
     * Retrieve recent system activity logs.
     *
     * Dashboard only exposes stored log fields, so resolving
     * polymorphic relations is unnecessary.
     */
    public function getRecentActivities(
        int $limit = 15,
        ?User $user = null
    ): Collection {
        return $this->scoped(
            ActivityLog::query(),
            $user
        )
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Fetch and format deadline monitoring items
     * for a selected date range.
     */
    protected function getDeadlineItems(
        string $startStr,
        string $endStr,
        string $category,
        ?User $user = null
    ): Collection {
        $taskPriorityColumnExists = Schema::hasColumn(
            'tasks',
            'priority'
        );

        /*
        |--------------------------------------------------------------------------
        | Campaign Deadlines
        |--------------------------------------------------------------------------
        */

        $campaigns = $this->scoped(
            Campaign::with('brand'),
            $user
        )
            ->whereNotIn(
                'status',
                [
                    'completed',
                    'cancelled',
                    'Completed',
                    'Finished',
                    'Archived',
                    'Cancelled',
                ]
            )
            ->whereNotNull('deadline')
            ->whereBetween(
                'deadline',
                [
                    $startStr . ' 00:00:00',
                    $endStr . ' 23:59:59',
                ]
            )
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function (Campaign $campaign) use ($category) {
                return [
                    'id' => $campaign->id,

                    'type' => 'Campaign',

                    'title' => $campaign->name,

                    'subtitle' => $campaign->brand !== null
                        ? $campaign->brand->name
                        : 'Standalone',

                    'deadline' => $campaign->deadline?->format(
                        'Y-m-d H:i'
                    ),

                    'status' => $campaign->status,

                    'status_code' => $category === 'today'
                        ? 'yellow'
                        : 'green',

                    'url' => "/campaigns/{$campaign->id}",
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Task Deadlines
        |--------------------------------------------------------------------------
        */

        $tasks = $this->scoped(
            Task::with('brand'),
            $user
        )
            ->whereNotIn(
                'progress_status',
                ['completed', 'cancelled']
            )
            ->whereNotNull('deadline')
            ->whereBetween(
                'deadline',
                [
                    $startStr . ' 00:00:00',
                    $endStr . ' 23:59:59',
                ]
            )
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(
                function (Task $task) use (
                    $category,
                    $taskPriorityColumnExists
                ) {
                    $isUrgent =
                        $taskPriorityColumnExists
                        && ($task->priority ?? null) === 'urgent';

                    return [
                        'id' => $task->id,

                        'type' => 'Task',

                        'title' => $task->name
                            ?? 'Untitled Task',

                        /*
                         * Task may be personal and therefore
                         * not associated with a Brand.
                         */
                        'subtitle' => $task->brand !== null
                            ? $task->brand->name
                            : 'Personal Task',

                        'deadline' => $task->deadline?->format(
                            'Y-m-d H:i'
                        ),

                        'status' => $task->progress_status
                            ?? 'unknown',

                        'status_code' => $isUrgent
                            ? 'red'
                            : (
                                $category === 'today'
                                ? 'yellow'
                                : 'green'
                            ),

                        'url' => "/tasks?task={$task->id}",
                    ];
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Promotion Deadlines
        |--------------------------------------------------------------------------
        */

        $promotions = $this->scoped(
            Promotion::with([
                'brand',
                'campaign',
            ]),
            $user
        )
            ->whereNotNull('end_date')
            ->whereBetween(
                'end_date',
                [
                    $startStr . ' 00:00:00',
                    $endStr . ' 23:59:59',
                ]
            )
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function (Promotion $promotion) use ($category) {
                return [
                    'id' => $promotion->id,

                    'type' => 'Promotion',

                    'title' => trim(
                        ($promotion->code ?? '')
                            . ' - '
                            . ($promotion->name ?? 'Promotion'),
                        ' -'
                    ),

                    /*
                     * Prefer direct Brand relationship.
                     * Fall back to Campaign name if Brand is null.
                     */
                    'subtitle' => $promotion->brand !== null
                        ? $promotion->brand->name
                        : (
                            $promotion->campaign !== null
                            ? $promotion->campaign->name
                            : 'Standalone'
                        ),

                    'deadline' => $promotion->end_date?->format(
                        'Y-m-d'
                    ),

                    'status' => $promotion->status
                        ?? 'unknown',

                    'status_code' => $category === 'today'
                        ? 'yellow'
                        : 'green',

                    'url' => "/promotions/{$promotion->id}",
                ];
            });

        return $campaigns
            ->concat($promotions)
            ->concat($tasks)
            ->sortBy('deadline')
            ->values();
    }

    /**
     * Retrieve overdue Campaign and Task items.
     */
    protected function getOverdueCampaigns(
        string $todayStr,
        ?User $user = null
    ): Collection {
        /*
        |--------------------------------------------------------------------------
        | Overdue Campaigns
        |--------------------------------------------------------------------------
        */

        $campaigns = $this->scoped(
            Campaign::with('brand'),
            $user
        )
            ->whereNotNull('deadline')
            ->whereDate(
                'deadline',
                '<',
                $todayStr
            )
            ->whereNotIn(
                'status',
                [
                    'completed',
                    'cancelled',
                    'Completed',
                    'Finished',
                    'Archived',
                    'Cancelled',
                ]
            )
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function (Campaign $campaign) {
                return [
                    'id' => $campaign->id,

                    'type' => 'Campaign',

                    'title' => $campaign->name
                        ?? 'Untitled Campaign',

                    /*
                     * Brand relation may be null.
                     */
                    'subtitle' => $campaign->brand !== null
                        ? $campaign->brand->name
                        : 'Standalone',

                    'deadline' => $campaign->deadline?->format(
                        'Y-m-d H:i'
                    ),

                    'status' => $campaign->status
                        ?? 'unknown',

                    'status_code' => 'red',

                    'url' => "/campaigns/{$campaign->id}",
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Overdue Tasks
        |--------------------------------------------------------------------------
        */

        $tasks = $this->scoped(
            Task::with('brand'),
            $user
        )
            ->whereNotIn(
                'progress_status',
                ['completed', 'cancelled']
            )
            ->whereNotNull('deadline')
            ->whereDate(
                'deadline',
                '<',
                $todayStr
            )
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function (Task $task) {
                return [
                    'id' => $task->id,

                    'type' => 'Task',

                    'title' => $task->name
                        ?? 'Untitled Task',

                    /*
                     * Personal Tasks may legitimately have
                     * no Brand relationship.
                     */
                    'subtitle' => $task->brand !== null
                        ? $task->brand->name
                        : 'Personal Task',

                    'deadline' => $task->deadline?->format(
                        'Y-m-d H:i'
                    ),

                    'status' => $task->progress_status
                        ?? 'unknown',

                    'status_code' => 'red',

                    'url' => "/tasks?task={$task->id}",
                ];
            });

        return $campaigns
            ->concat($tasks)
            ->sortBy('deadline')
            ->values();
    }

    /**
     * Retrieve Secure Links which are expiring
     * in the next seven days.
     */
    protected function getExpiringLinks(
        Carbon $now,
        ?User $user = null
    ): Collection {
        return $this->scoped(
            SecureLink::with('linkable'),
            $user
        )
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->whereBetween(
                'expires_at',
                [
                    $now,
                    $now->copy()->addDays(7),
                ]
            )
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(function (SecureLink $link) {
                return [
                    'id' => $link->id,

                    'type' => 'Secure Link ('
                        . class_basename(
                            $link->linkable_type
                        )
                        . ')',

                    'title' => $this->secureLinkTitle($link),

                    'subtitle' => $link->expires_at
                        ? 'Expires in '
                        . $link->expires_at
                        ->diffForHumans()
                        : 'Expiration unavailable',

                    'deadline' => $link->expires_at?->format(
                        'Y-m-d H:i'
                    ),

                    'status' => 'Expiring Soon',

                    'status_code' => 'yellow',

                    'url' => $this->secureLinkUrl($link),
                ];
            });
    }

    /**
     * Apply the authenticated user's RBAC data scope.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function scoped(
        Builder $query,
        ?User $user
    ): Builder {
        return $user === null
            ? $query
            : $this->dataScope->scope(
                $query,
                $user
            );
    }

    /**
     * Generate a cache key based on the user's
     * data access scope.
     */
    private function scopeKey(
        ?User $user
    ): string {
        if (
            $user === null
            || $this->dataScope->hasGlobalScope($user)
        ) {
            return 'global';
        }

        return 'user_' . $user->id;
    }

    /**
     * Resolve a readable Secure Link title.
     */
    private function secureLinkTitle(
        SecureLink $link
    ): string {
        $linkable = $link->linkable;

        if ($linkable instanceof PerformanceReport) {
            return $linkable->title
                ?? 'Performance Report';
        }

        if ($linkable instanceof Campaign) {
            return $linkable->name
                ?? 'Campaign';
        }

        if ($linkable instanceof Promotion) {
            return $linkable->name
                ?? 'Promotion';
        }

        if ($linkable instanceof Task) {
            return $linkable->name
                ?? 'Task';
        }

        return 'Public Review Link';
    }

    /**
     * Resolve destination URL for a Secure Link.
     */
    private function secureLinkUrl(
        SecureLink $link
    ): string {
        if (
            $link->linkable_type === Promotion::class
        ) {
            return "/promotions/{$link->linkable_id}";
        }

        if (
            $link->linkable_type === Task::class
        ) {
            return "/tasks?task={$link->linkable_id}";
        }

        if (
            $link->linkable_type === PerformanceReport::class
        ) {
            return "/performance-reports/{$link->linkable_id}";
        }

        return "/campaigns/{$link->linkable_id}";
    }
}
