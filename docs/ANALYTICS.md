# Pricing Analytics & Business Intelligence — SunTrack

> **Sprint 11 · Analytics Foundation & BI Aggregations**

---

## Table of Contents

1. [Overview](#overview)
2. [Pricing Analytics](#pricing-analytics)
   - [PricingAnalyticsRepository](#pricinganalyticsrepository)
   - [Core Metrics](#core-metrics)
   - [Margin Violations](#margin-violations)
   - [Discount Simulation](#discount-simulation)
3. [Business Intelligence](#business-intelligence)
   - [AnalyticsRepository](#analyticsrepository)
   - [Approval Performance Report](#approval-performance-report)
   - [Promotion Effectiveness Report](#promotion-effectiveness-report)
   - [Brand Activity Report](#brand-activity-report)
4. [API Endpoints](#api-endpoints)
5. [Caching Strategy](#caching-strategy)
6. [Financial Metric Notes](#financial-metric-notes)

---

## Overview

Sprint 11 introduces the **Analytics & Business Intelligence Foundation**, a two-pillar system:

1. **Pricing Analytics** — deep financial metrics on promotions, margins, revenue estimates, and profitability calculations at the variant level.
2. **Business Intelligence (BI)** — cross-entity aggregations that power executive reporting: approval rates, promotion effectiveness, and brand-level activity timelines.

Both pillars follow the **Repository Pattern** — all data access is encapsulated in repository classes; controllers contain zero raw database queries.

---

## Pricing Analytics

### PricingAnalyticsRepository

```
app/Repositories/Analytics/PricingAnalyticsRepository.php
```

The `PricingAnalyticsRepository` provides all financial aggregations related to pricing, margins, and revenue estimates. It is injected via the service container and cached automatically.

```php
namespace App\Repositories\Analytics;

interface PricingAnalyticsRepositoryInterface
{
    public function getMarginSummary(int $companyId, array $filters = []): array;
    public function getMarginViolations(int $companyId, array $filters = []): array;
    public function simulateDiscount(int $companyId, float $discountPercent, array $filters = []): array;
}
```

### Core Metrics

The following financial metrics are computed per company, with optional filtering by campaign, brand, or date range:

| Metric | Description | Formula |
|---|---|---|
| `avg_margin_absolute` | Average absolute margin per variant | `AVG(promo_price - cost_price)` |
| `avg_margin_pct` | Average margin as a percentage | `AVG((promo_price - cost_price) / cost_price * 100)` |
| `min_margin_absolute` | Lowest absolute margin (worst case) | `MIN(promo_price - cost_price)` |
| `min_margin_pct` | Lowest margin percentage | `MIN(...)` |
| `max_margin_absolute` | Highest absolute margin (best case) | `MAX(promo_price - cost_price)` |
| `max_margin_pct` | Highest margin percentage | `MAX(...)` |
| `total_estimated_revenue` | Estimated gross revenue | `SUM(promo_price * estimated_qty)` |
| `total_estimated_profit` | Estimated net profit | `SUM((promo_price - cost_price) * estimated_qty)` |
| `roi_pct` | Return on investment | `(total_estimated_profit / SUM(cost_price * estimated_qty)) * 100` |

#### Sample Response — `GET /api/v1/admin/analytics/pricing/summary`

```json
{
  "data": {
    "avg_margin_absolute": 12500.00,
    "avg_margin_pct": 18.4,
    "min_margin_absolute": -3000.00,
    "min_margin_pct": -4.2,
    "max_margin_absolute": 75000.00,
    "max_margin_pct": 62.1,
    "total_estimated_revenue": 4820000.00,
    "total_estimated_profit": 887240.00,
    "roi_pct": 22.6,
    "variant_count": 342,
    "currency": "IDR"
  },
  "meta": {
    "cached": true,
    "generated_at": "2025-11-14T09:00:00Z",
    "filters": { "campaign_id": null, "brand_id": null }
  }
}
```

---

### Margin Violations

**Margin violations** are cases where a variant's promotional price falls **below** the configured bottom price (`bottom_price`), creating a negative or unacceptable margin.

```php
// Violation condition
WHERE promo_price < bottom_price
```

#### Sample Response — `GET /api/v1/admin/analytics/pricing/violations`

```json
{
  "data": [
    {
      "promotion_id": 204,
      "promotion_name": "Brand A Flash Promo",
      "variant_id": 88,
      "variant_name": "SKU-0088 / 500ml",
      "promo_price": 45000,
      "bottom_price": 52000,
      "margin_deficit": -7000,
      "deficit_pct": -13.46
    }
  ],
  "meta": {
    "total_violations": 1,
    "cached": true
  }
}
```

Violations are displayed in the Admin Dashboard with a **red badge** and require corrective action before campaign approval.

---

### Discount Simulation

The **Discount Simulation** endpoint allows admins to model the financial impact of applying a percentage discount across all (or filtered) variants — without modifying live data.

#### Endpoint

```
POST /api/v1/admin/analytics/pricing/simulate
```

#### Request Body

```json
{
  "discount_percent": 15,
  "filters": {
    "campaign_id": 12,
    "brand_id": null,
    "variant_ids": []
  }
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `discount_percent` | `float` | ✅ | Discount to simulate (0.01–99.99) |
| `filters.campaign_id` | `integer` | ❌ | Limit simulation to a specific campaign |
| `filters.brand_id` | `integer` | ❌ | Limit simulation to a specific brand |
| `filters.variant_ids` | `integer[]` | ❌ | Limit simulation to specific variants |

#### Response

```json
{
  "data": {
    "discount_percent": 15,
    "simulated_revenue": 4097000.00,
    "simulated_profit": 482700.00,
    "simulated_roi_pct": 13.3,
    "revenue_delta": -723000.00,
    "profit_delta": -404540.00,
    "violations_introduced": 14,
    "variants_affected": 342
  }
}
```

> **Note:** Simulation results are **not cached** to ensure accuracy with the latest pricing data.

---

## Business Intelligence

### AnalyticsRepository

```
app/Repositories/Analytics/AnalyticsRepository.php
```

The `AnalyticsRepository` consolidates all BI aggregations. **Controllers must never contain raw database queries** — all DB interaction for analytics goes through this repository.

```php
namespace App\Repositories\Analytics;

interface AnalyticsRepositoryInterface
{
    public function getApprovalPerformance(int $companyId, array $filters = []): array;
    public function getPromotionEffectiveness(int $companyId, array $filters = []): array;
    public function getBrandActivity(int $companyId, int $brandId, array $filters = []): array;
}
```

---

### Approval Performance Report

Aggregates promotion approval decisions to measure the throughput and quality of the approval workflow.

#### Metrics

| Metric | Description |
|---|---|
| `total_decisions` | Total approve + reject actions in the period |
| `approved` | Count of approved promotions |
| `rejected` | Count of rejected promotions |
| `approval_rate_pct` | `(approved / total_decisions) * 100` |
| `per_brand` | Breakdown of the above metrics, per brand |

#### Sample Response — `GET /api/v1/admin/analytics/approval-performance`

```json
{
  "data": {
    "total_decisions": 184,
    "approved": 152,
    "rejected": 32,
    "approval_rate_pct": 82.6,
    "per_brand": [
      {
        "brand_id": 5,
        "brand_name": "Brand Alfa",
        "approved": 48,
        "rejected": 6,
        "approval_rate_pct": 88.9
      },
      {
        "brand_id": 9,
        "brand_name": "Brand Beta",
        "approved": 31,
        "rejected": 14,
        "approval_rate_pct": 68.9
      }
    ]
  }
}
```

---

### Promotion Effectiveness Report

Measures the lifecycle outcomes of promotions — how many are approved, pending, or rejected — and how much campaign coverage has been achieved.

#### Metrics

| Metric | Description |
|---|---|
| `total_promotions` | All promotions in the period |
| `approved` | Promotions with `status = approved` |
| `rejected` | Promotions with `status = rejected` |
| `pending` | Promotions with `status = pending` |
| `approval_rate_pct` | `(approved / total_promotions) * 100` |
| `campaign_coverage_pct` | % of active campaigns that have at least one approved promotion |

#### Sample Response — `GET /api/v1/admin/analytics/promotion-effectiveness`

```json
{
  "data": {
    "total_promotions": 320,
    "approved": 241,
    "rejected": 47,
    "pending": 32,
    "approval_rate_pct": 75.3,
    "campaign_coverage_pct": 86.4
  }
}
```

---

### Brand Activity Report

Provides a chronological timeline of promotion submissions for a specific brand, enabling brand managers and admins to audit submission patterns and velocity.

#### Sample Response — `GET /api/v1/admin/analytics/brand-activity/{brandId}`

```json
{
  "data": {
    "brand_id": 5,
    "brand_name": "Brand Alfa",
    "period": {
      "from": "2025-10-01",
      "to": "2025-11-14"
    },
    "timeline": [
      {
        "date": "2025-10-03",
        "submitted": 4,
        "approved": 3,
        "rejected": 1,
        "pending": 0
      },
      {
        "date": "2025-10-10",
        "submitted": 7,
        "approved": 5,
        "rejected": 0,
        "pending": 2
      }
    ],
    "summary": {
      "total_submitted": 11,
      "total_approved": 8,
      "total_rejected": 1,
      "total_pending": 2
    }
  }
}
```

---

## API Endpoints

### Pricing Analytics

| Endpoint | Method | Description |
|---|---|---|
| `/api/v1/admin/analytics/pricing/summary` | `GET` | Margin summary (avg, min, max, revenue, profit, ROI) |
| `/api/v1/admin/analytics/pricing/violations` | `GET` | List all margin violation promotions |
| `/api/v1/admin/analytics/pricing/simulate` | `POST` | Simulate financial impact of a discount |

### Business Intelligence

| Endpoint | Method | Description |
|---|---|---|
| `/api/v1/admin/analytics/approval-performance` | `GET` | Approval rate and per-brand breakdown |
| `/api/v1/admin/analytics/promotion-effectiveness` | `GET` | Promotion status ratios and campaign coverage |
| `/api/v1/admin/analytics/brand-activity/{brandId}` | `GET` | Chronological timeline for a specific brand |

### Shared Query Parameters

| Parameter | Type | Description |
|---|---|---|
| `date_from` | `date` | Filter start date (`Y-m-d`) |
| `date_to` | `date` | Filter end date (`Y-m-d`) |
| `campaign_id` | `integer` | Scope results to a specific campaign |
| `brand_id` | `integer` | Scope results to a specific brand |

---

## Caching Strategy

All analytics results (except discount simulation) are cached via **Redis tags** to enable granular invalidation:

| Property | Value |
|---|---|
| Cache TTL | **600 seconds** (10 minutes) |
| Cache Store | `redis` |
| Cache Tags | `['analytics', 'bi']` |
| Cache Key Pattern | `analytics:{companyId}:{report}:{md5(filters)}` |

```php
return Cache::tags(['analytics', 'bi'])
    ->remember($cacheKey, 600, fn () =>
        $this->analyticsRepository->getApprovalPerformance($companyId, $filters)
    );
```

### Cache Invalidation

Analytics caches are invalidated when:

- A promotion status changes (`approved`, `rejected`)
- A campaign is created, updated, or deleted
- A pricing record is modified

```bash
# Manually flush all analytics cache
docker compose exec app php artisan cache:tags:flush analytics bi
```

---

## Financial Metric Notes

> [!IMPORTANT]
> All financial figures in SunTrack Analytics are **estimates** derived from promotional pricing data. They are intended for planning and trend analysis only — not for accounting or regulatory reporting.

Key assumptions:

| Assumption | Detail |
|---|---|
| Revenue basis | `promo_price × variant mappings` (not actual transaction data) |
| Cost basis | `cost_price` field on the `promotion_variants` table |
| Quantity | `estimated_qty` where provided; defaults to `1` if not set |
| Currency | All figures are in the company's configured base currency (`IDR` by default) |
| Exchange rates | Not applied — cross-currency promotions are reported in their local currency |

---

*Last updated: Sprint 11 · SunTrack Platform*
