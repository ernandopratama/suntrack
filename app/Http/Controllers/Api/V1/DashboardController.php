<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Repositories\DashboardRepository;
use App\Services\Reporting\ReportingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DashboardRepository $repository
    ) {}

    /**
     * Retrieve aggregated KPI metrics, deadline monitoring items, and recent activities.
     * Engineered for high performance with backend SQL aggregation and repository pattern.
     */
    public function stats(Request $request): JsonResponse
    {
        $now = now();
        $todayStr = $now->toDateString();
        $tomorrowStr = $now->copy()->addDay()->toDateString();
        $next7DaysStr = $now->copy()->addDays(7)->toDateString();

        $kpis = $this->repository->getKpiStats($todayStr);
        $deadlines = $this->repository->getDeadlines($todayStr, $tomorrowStr, $next7DaysStr, $now);
        $recentActivities = $this->repository->getRecentActivities(15);

        $payload = [
            'kpi' => $kpis,
            'deadlines' => $deadlines,
            'recent_activities' => $recentActivities,
            'server_time' => $now->toIso8601String(),
        ];

        return $this->success('Dashboard operational statistics retrieved successfully.', [
            'dashboard' => new DashboardResource((object) $payload),
        ]);
    }

    /**
     * Export a sample report directly from the Dashboard Operational Command Center (Refinement #6).
     */
    public function exportReport(Request $request, ReportingService $reporting)
    {
        $type = $request->get('type', 'campaign');
        $format = $request->get('format', 'csv');

        return $reporting->export($type, $format, $request->all());
    }
}
