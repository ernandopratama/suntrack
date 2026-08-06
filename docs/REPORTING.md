# BI Reporting Foundation — SunTrack

> **Sprint 11 · Business Intelligence Reporting Layer**

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Reports](#reports)
   - [Approval Performance Report](#1-approval-performance-report)
   - [Promotion Effectiveness Report](#2-promotion-effectiveness-report)
   - [Brand Activity Report](#3-brand-activity-report)
4. [API Endpoints](#api-endpoints)
5. [Caching Strategy](#caching-strategy)
6. [Backward Compatibility](#backward-compatibility)
7. [Docker & Debugging](#docker--debugging)
8. [Future Roadmap](#future-roadmap)

---

## Overview

The **BI Reporting Foundation** establishes the reporting layer for SunTrack's Business Intelligence capabilities. Introduced in Sprint 11, this module provides three production-ready reports that give administrators and brand managers actionable insights into approval workflows, promotion lifecycle outcomes, and brand-level activity.

The reporting layer is designed with two non-negotiable constraints:

1. **Zero breaking changes** — all new endpoints extend the existing `ReportService` adapter pattern introduced in Sprint 8.
2. **Repository-first** — all database aggregations live in `AnalyticsRepository`; `ReportController` contains only orchestration logic.

---

## Architecture

The reporting stack follows a strict layered architecture:

```
┌─────────────────────────────────────────────┐
│             HTTP Request                    │
└─────────────────────┬───────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────┐
│            ReportController                 │
│  - Validates request parameters             │
│  - Delegates to ReportService               │
│  - Returns JSON response                    │
└─────────────────────┬───────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────┐
│             ReportService                   │
│  (Sprint 8 Adapter — extended, not changed) │
│  - Applies authorization checks             │
│  - Resolves cache or calls repository       │
└──────────┬──────────────────────────────────┘
           │             │
           ▼             ▼
┌──────────────┐  ┌──────────────────────────┐
│ Redis Cache  │  │   AnalyticsRepository    │
│ (600s TTL)   │  │  (all DB aggregations)   │
└──────────────┘  └────────────┬─────────────┘
                               │
                               ▼
                  ┌────────────────────────────┐
                  │   Database (MySQL/PgSQL)   │
                  │  promotions, campaigns,    │
                  │  brands, approval_logs     │
                  └────────────────────────────┘
```

### Key Classes

| Class | Path | Responsibility |
|---|---|---|
| `ReportController` | `app/Http/Controllers/Admin/ReportController.php` | HTTP layer; validation; response shaping |
| `ReportService` | `app/Services/Report/ReportService.php` | Orchestration; caching; authorization |
| `AnalyticsRepository` | `app/Repositories/Analytics/AnalyticsRepository.php` | All DB aggregations; no HTTP awareness |

---

## Reports

### 1. Approval Performance Report

Measures the throughput and quality of the promotion approval workflow over a configurable time window.

#### Endpoint

```
GET /api/v1/admin/reports/approval-performance
```

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `date_from` | `date` | ❌ | 30 days ago | Start of reporting window (`Y-m-d`) |
| `date_to` | `date` | ❌ | today | End of reporting window (`Y-m-d`) |
| `brand_id` | `integer` | ❌ | all brands | Filter by a specific brand |

#### Metrics

| Metric | Description |
|---|---|
| `total_decisions` | Total approve + reject actions in the period |
| `approved` | Count of approved promotions |
| `rejected` | Count of rejected promotions |
| `approval_rate_pct` | `(approved / total_decisions) * 100` |
| `per_brand` | Per-brand breakdown of the above metrics |

#### Sample Response

```json
{
  "report": "approval-performance",
  "period": {
    "from": "2025-10-15",
    "to": "2025-11-14"
  },
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
      },
      {
        "brand_id": 12,
        "brand_name": "Brand Gamma",
        "approved": 73,
        "rejected": 12,
        "approval_rate_pct": 85.9
      }
    ]
  },
  "meta": {
    "cached": true,
    "cache_expires_at": "2025-11-14T09:10:00Z",
    "generated_at": "2025-11-14T09:00:22Z"
  }
}
```

---

### 2. Promotion Effectiveness Report

Provides a lifecycle view of all promotions within a period: how many are approved, pending, or rejected, and the proportion of active campaigns that have at least one approved promotion.

#### Endpoint

```
GET /api/v1/admin/reports/promotion-effectiveness
```

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `date_from` | `date` | ❌ | 30 days ago | Start of reporting window (`Y-m-d`) |
| `date_to` | `date` | ❌ | today | End of reporting window (`Y-m-d`) |
| `campaign_id` | `integer` | ❌ | all campaigns | Filter by a specific campaign |

#### Metrics

| Metric | Description |
|---|---|
| `total_promotions` | All promotions created in the period |
| `approved` | Count with `status = approved` |
| `rejected` | Count with `status = rejected` |
| `pending` | Count with `status = pending` |
| `approval_rate_pct` | `(approved / total_promotions) * 100` |
| `campaign_coverage_pct` | % of active campaigns with ≥ 1 approved promotion |

#### Sample Response

```json
{
  "report": "promotion-effectiveness",
  "period": {
    "from": "2025-10-15",
    "to": "2025-11-14"
  },
  "data": {
    "total_promotions": 320,
    "approved": 241,
    "rejected": 47,
    "pending": 32,
    "approval_rate_pct": 75.3,
    "campaign_coverage_pct": 86.4,
    "status_breakdown_pct": {
      "approved": 75.3,
      "rejected": 14.7,
      "pending": 10.0
    }
  },
  "meta": {
    "cached": true,
    "cache_expires_at": "2025-11-14T09:10:00Z",
    "generated_at": "2025-11-14T09:00:31Z"
  }
}
```

---

### 3. Brand Activity Report

Provides a per-brand chronological timeline of promotion submissions, enabling brand managers and administrators to audit submission velocity and identify anomalies.

#### Endpoint

```
GET /api/v1/admin/reports/brand-activity/{brandId}
```

#### Path Parameters

| Parameter | Type | Description |
|---|---|---|
| `brandId` | `integer` | ID of the brand to report on |

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `date_from` | `date` | ❌ | 30 days ago | Start of reporting window (`Y-m-d`) |
| `date_to` | `date` | ❌ | today | End of reporting window (`Y-m-d`) |
| `granularity` | `string` | ❌ | `day` | Group timeline by `day`, `week`, or `month` |

#### Metrics (per timeline entry)

| Metric | Description |
|---|---|
| `date` | Start of the time bucket (`Y-m-d`) |
| `submitted` | Total promotions submitted in this bucket |
| `approved` | Promotions approved in this bucket |
| `rejected` | Promotions rejected in this bucket |
| `pending` | Promotions still pending at end of bucket |

#### Sample Response

```json
{
  "report": "brand-activity",
  "brand": {
    "id": 5,
    "name": "Brand Alfa"
  },
  "period": {
    "from": "2025-10-01",
    "to": "2025-11-14",
    "granularity": "day"
  },
  "data": {
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
      },
      {
        "date": "2025-10-24",
        "submitted": 12,
        "approved": 9,
        "rejected": 2,
        "pending": 1
      }
    ],
    "summary": {
      "total_submitted": 23,
      "total_approved": 17,
      "total_rejected": 3,
      "total_pending": 3,
      "approval_rate_pct": 73.9
    }
  },
  "meta": {
    "cached": true,
    "cache_expires_at": "2025-11-14T09:10:00Z"
  }
}
```

---

## API Endpoints

### Summary

| Report | Endpoint | Method |
|---|---|---|
| Approval Performance | `/api/v1/admin/reports/approval-performance` | `GET` |
| Promotion Effectiveness | `/api/v1/admin/reports/promotion-effectiveness` | `GET` |
| Brand Activity | `/api/v1/admin/reports/brand-activity/{brandId}` | `GET` |

### Authentication

All report endpoints require:
- `Authorization: Bearer {token}` header
- `role:admin` or `role:manager` middleware

### Error Responses

| Status | Scenario |
|---|---|
| `401 Unauthorized` | Missing or invalid token |
| `403 Forbidden` | Authenticated but insufficient role |
| `404 Not Found` | `brandId` does not exist in the current company |
| `422 Unprocessable Entity` | Invalid date range (e.g., `date_from > date_to`) |

---

## Caching Strategy

Reports are cached using Redis to prevent expensive DB aggregations from running on every request.

| Property | Value |
|---|---|
| Cache TTL | **600 seconds** (10 minutes) |
| Cache Store | `redis` |
| Cache Key Pattern | `report:{companyId}:{report}:{dateFrom}:{dateTo}:{extras}` |

#### Cache Key Examples

```
report:42:approval-performance:2025-10-15:2025-11-14:all
report:42:promotion-effectiveness:2025-10-15:2025-11-14:campaign-null
report:42:brand-activity:2025-10-01:2025-11-14:brand-5:granularity-day
```

#### Cache Invalidation

Report caches are automatically invalidated when:
- A promotion status changes (`approved` / `rejected`)
- A campaign is created or archived
- A brand is updated

```bash
# Manually bust all report caches for a company
docker compose exec app php artisan cache:tags:flush report:42

# Bust a specific report type
docker compose exec app php artisan cache:tags:flush report:42:approval-performance
```

---

## Backward Compatibility

> [!IMPORTANT]
> Sprint 11 reporting introduces **zero breaking changes** to existing Sprint 8 report functionality.

The Sprint 8 `ReportService` adapter pattern is **extended, not replaced**:

| Sprint | What changed |
|---|---|
| Sprint 8 | `ReportService` introduced as adapter for existing report exports |
| Sprint 11 | `AnalyticsRepository` added; `ReportService` extended with new delegation methods |

Existing report endpoints, contracts, and tests from Sprint 8 remain fully functional. Sprint 11 adds new methods to `ReportService` and `AnalyticsRepository` without modifying any existing method signatures.

---

## Docker & Debugging

### Access AnalyticsRepository Directly (Tinker)

For admin debugging and ad-hoc queries without going through the HTTP layer:

```bash
docker compose exec app php artisan tinker
```

```php
// Inside tinker:

// Resolve from service container
$repo = app(\App\Repositories\Analytics\AnalyticsRepository::class);

// Approval performance for company 42, last 30 days
$result = $repo->getApprovalPerformance(42, [
    'date_from' => now()->subDays(30)->toDateString(),
    'date_to'   => now()->toDateString(),
]);

dd($result);

// Promotion effectiveness
$result = $repo->getPromotionEffectiveness(42, [
    'date_from' => '2025-10-01',
    'date_to'   => '2025-11-14',
]);

dd($result);

// Brand activity for brand 5
$result = $repo->getBrandActivity(42, 5, [
    'date_from'   => '2025-10-01',
    'date_to'     => '2025-11-14',
    'granularity' => 'week',
]);

dd($result);
```

### Verify Cache State

```bash
# Check Redis for report cache keys
docker compose exec redis redis-cli KEYS "report:42:*"

# Inspect a specific cache entry (decoded)
docker compose exec app php artisan tinker --execute="
  echo Cache::get('report:42:approval-performance:2025-10-15:2025-11-14:all') ? 'HIT' : 'MISS';
"
```

---

## Future Roadmap

| Feature | Trigger | Description |
|---|---|---|
| **Excel Export** | `?format=export` query param | Generates `.xlsx` via existing `ExportService` when appended to any report endpoint |
| **PDF Export** | `?format=pdf` | Generates styled PDF report via `DomPDF` / `Snappy` |
| **Scheduled Email Reports** | Cron config | Weekly/monthly reports emailed to configured admin recipients |
| **Custom Date Presets** | UI only | Quick-select for "Last 7 days", "This month", "Last quarter" |
| **Drill-down** | Planned | Click into a per-brand row to navigate directly to the Brand Activity report |

> When the `ExportService` integration is ready, adding export support requires only a `format` parameter check in `ReportController` — no changes to `AnalyticsRepository` or `ReportService`.

---

*Last updated: Sprint 11 · SunTrack Platform*
