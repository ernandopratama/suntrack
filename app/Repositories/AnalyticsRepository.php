<?php

namespace App\Repositories;

use App\Models\Brand;
use App\Models\Promotion;
use App\Services\Cache\CacheService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Repository — aggregates BI data via Repository Pattern (ADR-027 / Sprint 11).
 * All queries use DB aggregation; zero business logic in Controller.
 */
class AnalyticsRepository
{
    public function __construct(
        protected CacheService $cache = new CacheService
    ) {}

    /**
     * Approval Performance Report: approval rate, avg time-to-approve per brand.
     *
     * @return array<string, mixed>
     */
    public function getApprovalPerformanceReport(string $dateFrom, string $dateTo, int|string|array|null $scope): array
    {
        $scopeKey = md5(json_encode($scope) ?: 'global');
        $cacheKey = "bi_approval_{$scopeKey}_{$dateFrom}_{$dateTo}";

        return $this->cache->remember(['analytics', 'bi', 'approval'], $cacheKey, 600, function () use ($dateFrom, $dateTo, $scope) {
            $totals = DB::table('approval_histories as ah')
                ->join('promotion_variant as pv', 'ah.promotion_variant_id', '=', 'pv.id')
                ->join('promotions as pr', 'pv.promotion_id', '=', 'pr.id')
                ->join('brands as b', 'pr.brand_id', '=', 'b.id');
            $this->applyBrandScope($totals, $scope);
            $totals = $totals
                ->whereBetween('ah.created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->select([
                    DB::raw('COUNT(*) as total_decisions'),
                    DB::raw('COUNT(CASE WHEN ah.new_status = "Approved" THEN 1 END) as approved'),
                    DB::raw('COUNT(CASE WHEN ah.new_status = "Rejected" THEN 1 END) as rejected'),
                ])
                ->first();

            $total = (int) ($totals->total_decisions ?? 0);
            $approved = (int) ($totals->approved ?? 0);
            $rejected = (int) ($totals->rejected ?? 0);
            $rate = $total > 0 ? round(($approved / $total) * 100, 2) : 0.0;

            // Per-brand breakdown
            $perBrand = DB::table('approval_histories as ah')
                ->join('promotion_variant as pv', 'ah.promotion_variant_id', '=', 'pv.id')
                ->join('promotions as pr', 'pv.promotion_id', '=', 'pr.id')
                ->join('brands as b', 'pr.brand_id', '=', 'b.id');
            $this->applyBrandScope($perBrand, $scope);
            $perBrand = $perBrand
                ->whereBetween('ah.created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->select([
                    'b.name as brand_name',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COUNT(CASE WHEN ah.new_status = "Approved" THEN 1 END) as approved'),
                    DB::raw('COUNT(CASE WHEN ah.new_status = "Rejected" THEN 1 END) as rejected'),
                ])
                ->groupBy('b.id', 'b.name')
                ->orderBy('total', 'desc')
                ->get()
                ->map(fn ($r) => [
                    'brand' => $r->brand_name,
                    'total' => $r->total,
                    'approved' => $r->approved,
                    'rejected' => $r->rejected,
                    'approval_rate' => $r->total > 0 ? round(($r->approved / $r->total) * 100, 2) : 0.0,
                ])->values()->all();

            return [
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'summary' => [
                    'total_decisions' => $total,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'approval_rate_pct' => $rate,
                ],
                'by_brand' => $perBrand,
            ];
        });
    }

    /**
     * Promotion Effectiveness Report: approved vs total, campaign coverage.
     *
     * @return array<string, mixed>
     */
    public function getPromotionEffectivenessReport(string $dateFrom, string $dateTo, int|string|array|null $scope): array
    {
        $scopeKey = md5(json_encode($scope) ?: 'global');
        $cacheKey = "bi_promo_effectiveness_{$scopeKey}_{$dateFrom}_{$dateTo}";

        return $this->cache->remember(['analytics', 'bi', 'promotions'], $cacheKey, 600, function () use ($dateFrom, $dateTo, $scope) {
            $data = DB::table('promotions as pr')
                ->join('brands as b', 'pr.brand_id', '=', 'b.id');
            $this->applyBrandScope($data, $scope);
            $data = $data
                ->whereBetween('pr.created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COUNT(CASE WHEN pr.status = "Approved" THEN 1 END) as approved'),
                    DB::raw('COUNT(CASE WHEN pr.status = "Rejected" THEN 1 END) as rejected'),
                    DB::raw('COUNT(CASE WHEN pr.status = "Pending" THEN 1 END) as pending'),
                    DB::raw('COUNT(CASE WHEN pr.campaign_id IS NOT NULL THEN 1 END) as with_campaign'),
                ])
                ->first();

            $total = (int) ($data->total ?? 0);
            $approved = (int) ($data->approved ?? 0);
            $rate = $total > 0 ? round(($approved / $total) * 100, 2) : 0.0;
            $covPct = $total > 0 ? round(((int) ($data->with_campaign ?? 0) / $total) * 100, 2) : 0.0;

            return [
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'total_promotions' => $total,
                'approved' => $approved,
                'rejected' => (int) ($data->rejected ?? 0),
                'pending' => (int) ($data->pending ?? 0),
                'approval_rate_pct' => $rate,
                'campaign_coverage_pct' => $covPct,
            ];
        });
    }

    /**
     * Brand Activity Report: timeline of a brand's promotion activity.
     *
     * @return array<string, mixed>
     */
    public function getBrandActivityReport(string $brandId, string $dateFrom, string $dateTo): array
    {
        $cacheKey = "bi_brand_activity_{$brandId}_{$dateFrom}_{$dateTo}";

        return $this->cache->remember(['analytics', 'bi', 'brand'], $cacheKey, 600, function () use ($brandId, $dateFrom, $dateTo) {
            $brand = Brand::find($brandId);
            if (! $brand) {
                return ['error' => 'Brand not found.'];
            }

            $promotions = DB::table('promotions')
                ->where('brand_id', $brandId)
                ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->select([
                    DB::raw('COUNT(*) as total_submitted'),
                    DB::raw('COUNT(CASE WHEN status = "Approved" THEN 1 END) as approved'),
                    DB::raw('COUNT(CASE WHEN status = "Rejected" THEN 1 END) as rejected'),
                    DB::raw('COUNT(CASE WHEN status = "Pending" THEN 1 END) as pending'),
                    DB::raw('COUNT(CASE WHEN status = "Partially Approved" THEN 1 END) as partially_approved'),
                ])
                ->first();

            return [
                'brand' => ['id' => $brand->id, 'name' => $brand->name],
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'promotions' => [
                    'total_submitted' => (int) ($promotions->total_submitted ?? 0),
                    'approved' => (int) ($promotions->approved ?? 0),
                    'rejected' => (int) ($promotions->rejected ?? 0),
                    'pending' => (int) ($promotions->pending ?? 0),
                    'partially_approved' => (int) ($promotions->partially_approved ?? 0),
                ],
            ];
        });
    }

    private function applyBrandScope(Builder $query, int|string|array|null $scope): void
    {
        if (is_array($scope)) {
            $query->whereIn('b.id', $scope);

            return;
        }

        if ($scope !== null) {
            $query->where('b.company_id', $scope);
        }
    }
}
