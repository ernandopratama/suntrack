<?php

namespace App\Http\Controllers\Api\V1;

use App\Repositories\AnalyticsRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * BI Report Controller — serves Executive Intelligence Reports via Repository Pattern (Sprint 11).
 */
class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AnalyticsRepository $analytics = new AnalyticsRepository()
    ) {}

    /**
     * GET /api/v1/admin/reports/approval-performance
     */
    public function approvalPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $companyId = $request->user()->company_id;
        $data = $this->analytics->getApprovalPerformanceReport(
            $request->input('date_from'),
            $request->input('date_to'),
            $companyId
        );

        return $this->success('Approval performance report generated.', $data);
    }

    /**
     * GET /api/v1/admin/reports/promotion-effectiveness
     */
    public function promotionEffectiveness(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $companyId = $request->user()->company_id;
        $data = $this->analytics->getPromotionEffectivenessReport(
            $request->input('date_from'),
            $request->input('date_to'),
            $companyId
        );

        return $this->success('Promotion effectiveness report generated.', $data);
    }

    /**
     * GET /api/v1/admin/reports/brand-activity/{brandId}
     */
    public function brandActivity(Request $request, string $brandId): JsonResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $data = $this->analytics->getBrandActivityReport(
            $brandId,
            $request->input('date_from'),
            $request->input('date_to')
        );

        return $this->success('Brand activity report generated.', $data);
    }
}
