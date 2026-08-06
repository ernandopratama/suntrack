<?php

namespace App\Http\Controllers\Api\V1;

use App\Repositories\PricingAnalyticsRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Pricing Analytics Controller (Sprint 11).
 */
class PricingAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PricingAnalyticsRepository $repo = new PricingAnalyticsRepository()
    ) {}

    /**
     * GET /api/v1/admin/analytics/pricing/overview
     */
    public function overview(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $data = $this->repo->getPricingOverview($companyId);
        return $this->success('Pricing analytics overview retrieved.', $data);
    }

    /**
     * GET /api/v1/admin/analytics/pricing/margin-violations
     */
    public function marginViolations(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage   = (int) $request->input('per_page', 20);
        $data      = $this->repo->getMarginViolations($companyId, $perPage);
        return $this->success('Margin violations retrieved.', $data);
    }

    /**
     * POST /api/v1/admin/analytics/pricing/simulate
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'discount_percent' => ['required', 'numeric', 'min:0.01', 'max:99.99'],
        ]);

        $companyId      = $request->user()->company_id;
        $discountPct    = (float) $request->input('discount_percent');
        $data           = $this->repo->simulateDiscount($companyId, $discountPct);

        return $this->success('Discount simulation complete.', $data);
    }
}
