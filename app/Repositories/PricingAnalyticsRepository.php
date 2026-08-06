<?php

namespace App\Repositories;

use App\Models\Variant;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\DB;

/**
 * Pricing Analytics Repository — aggregates margin, revenue, and ROI data (Sprint 11).
 */
class PricingAnalyticsRepository
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Get comprehensive pricing analytics overview for a company's promotions.
     *
     * @return array<string, mixed>
     */
    public function getPricingOverview(int|string $companyId): array
    {
        return $this->cache->remember(['analytics', 'pricing'], "pricing_overview_{$companyId}_" . now()->format('Y-m-d-H'), 600, function () use ($companyId) {

            // Aggregate from promotion_variant join with variant bottom_price for margin calculations
            $raw = DB::table('promotion_variant as pv')
                ->join('variants as v', 'pv.variant_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->join('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('b.company_id', $companyId)
                ->whereNotNull('pv.promo_price')
                ->select([
                    DB::raw('COUNT(*) as total_mappings'),
                    DB::raw('AVG(v.normal_price) as avg_normal_price'),
                    DB::raw('AVG(pv.promo_price) as avg_promo_price'),
                    DB::raw('AVG(v.bottom_price) as avg_bottom_price'),
                    DB::raw('AVG(pv.promo_price - v.bottom_price) as avg_margin_absolute'),
                    DB::raw('MIN(pv.promo_price - v.bottom_price) as min_margin_absolute'),
                    DB::raw('MAX(pv.promo_price - v.bottom_price) as max_margin_absolute'),
                    DB::raw('SUM(pv.promo_price) as total_estimated_revenue'),
                    DB::raw('SUM(pv.promo_price - v.bottom_price) as total_estimated_profit'),
                    DB::raw('COUNT(CASE WHEN pv.promo_price < v.bottom_price THEN 1 END) as margin_violations'),
                ])
                ->first();

            if (!$raw || $raw->total_mappings === 0) {
                return $this->emptyOverview();
            }

            $avgNormal  = (float) ($raw->avg_normal_price ?? 0);
            $avgPromo   = (float) ($raw->avg_promo_price ?? 0);
            $avgBottom  = (float) ($raw->avg_bottom_price ?? 0);
            $avgMarginAbs = (float) ($raw->avg_margin_absolute ?? 0);
            $minMarginAbs = (float) ($raw->min_margin_absolute ?? 0);
            $maxMarginAbs = (float) ($raw->max_margin_absolute ?? 0);

            // Margin % relative to promo price
            $avgMarginPct = $avgPromo > 0 ? round(($avgMarginAbs / $avgPromo) * 100, 2) : 0.0;
            $minMarginPct = $avgPromo > 0 ? round(($minMarginAbs / max($avgPromo, 1)) * 100, 2) : 0.0;
            $maxMarginPct = $avgPromo > 0 ? round(($maxMarginAbs / max($avgPromo, 1)) * 100, 2) : 0.0;

            // ROI Foundation: (Total Profit / Total Revenue) * 100
            $totalRevenue = (float) ($raw->total_estimated_revenue ?? 0);
            $totalProfit  = (float) ($raw->total_estimated_profit ?? 0);
            $roiPercent   = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0.0;

            return [
                'total_promotion_variant_mappings' => (int) $raw->total_mappings,
                'margin_violations'                => (int) $raw->margin_violations,
                'pricing' => [
                    'avg_normal_price' => round($avgNormal, 2),
                    'avg_promo_price'  => round($avgPromo, 2),
                    'avg_bottom_price' => round($avgBottom, 2),
                ],
                'margin' => [
                    'avg_margin_absolute' => round($avgMarginAbs, 2),
                    'avg_margin_pct'      => $avgMarginPct,
                    'min_margin_absolute' => round($minMarginAbs, 2),
                    'min_margin_pct'      => $minMarginPct,
                    'max_margin_absolute' => round($maxMarginAbs, 2),
                    'max_margin_pct'      => $maxMarginPct,
                ],
                'financial' => [
                    'total_estimated_revenue' => round($totalRevenue, 2),
                    'total_estimated_profit'  => round($totalProfit, 2),
                    'roi_pct'                 => $roiPercent,
                    'note'                    => 'Estimates based on promo_price × mapped variants. Actual revenue depends on sales volume.',
                ],
            ];
        });
    }

    /**
     * Get promotion-variant pairs where promo_price is below bottom_price.
     */
    public function getMarginViolations(int|string $companyId, int $perPage = 20): array
    {
        $violations = DB::table('promotion_variant as pv')
            ->join('variants as v', 'pv.variant_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('brands as b', 'p.brand_id', '=', 'b.id')
            ->join('promotions as pr', 'pv.promotion_id', '=', 'pr.id')
            ->where('b.company_id', $companyId)
            ->whereNotNull('pv.promo_price')
            ->whereRaw('pv.promo_price < v.bottom_price')
            ->select([
                'pr.id as promotion_id',
                'pr.code as promotion_code',
                'pr.name as promotion_name',
                'v.id as variant_id',
                'v.name as variant_name',
                'v.sku as variant_sku',
                'pv.promo_price',
                'v.bottom_price',
                DB::raw('(v.bottom_price - pv.promo_price) as margin_gap'),
                'p.name as product_name',
            ])
            ->orderBy('margin_gap', 'desc')
            ->paginate($perPage);

        return [
            'violations' => $violations,
            'total'      => $violations->total(),
        ];
    }

    /**
     * Simulate pricing impact: "If discount X%, how many mappings will violate margin?"
     */
    public function simulateDiscount(int|string $companyId, float $discountPercent): array
    {
        $raw = DB::table('promotion_variant as pv')
            ->join('variants as v', 'pv.variant_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('brands as b', 'p.brand_id', '=', 'b.id')
            ->where('b.company_id', $companyId)
            ->whereNotNull('pv.promo_price')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN pv.promo_price * ? < v.bottom_price THEN 1 END) as projected_violations'),
            ])
            ->addBinding([(100 - $discountPercent) / 100], 'select')
            ->first();

        $total      = (int) ($raw?->total ?? 0);
        $violations = (int) ($raw?->projected_violations ?? 0);
        $safeCount  = $total - $violations;

        return [
            'discount_percent'      => $discountPercent,
            'total_mappings'        => $total,
            'projected_violations'  => $violations,
            'projected_safe'        => $safeCount,
            'violation_rate_pct'    => $total > 0 ? round(($violations / $total) * 100, 2) : 0.0,
            'recommendation'        => $violations === 0
                ? "Safe: All mappings will remain above bottom price at {$discountPercent}% discount."
                : "Warning: {$violations} mappings will fall below bottom price at {$discountPercent}% discount.",
        ];
    }

    protected function emptyOverview(): array
    {
        return [
            'total_promotion_variant_mappings' => 0,
            'margin_violations' => 0,
            'pricing' => ['avg_normal_price' => 0, 'avg_promo_price' => 0, 'avg_bottom_price' => 0],
            'margin' => ['avg_margin_absolute' => 0, 'avg_margin_pct' => 0, 'min_margin_absolute' => 0, 'min_margin_pct' => 0, 'max_margin_absolute' => 0, 'max_margin_pct' => 0],
            'financial' => ['total_estimated_revenue' => 0, 'total_estimated_profit' => 0, 'roi_pct' => 0, 'note' => 'No pricing data found.'],
        ];
    }
}
