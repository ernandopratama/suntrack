# SunTrack Caching Strategy & Guidelines (ADR-023)

This document defines the official caching architecture, naming conventions, tag taxonomies, TTL policies, and invalidation workflows for SunTrack Enterprise v1.0. The system leverages **Redis Tag-Based Caching** via `App\Services\Cache\CacheService` to achieve high-throughput data access and sub-15ms dashboard latency.

---

## 1. Key Naming Convention

To prevent key collisions across multitenant companies, environments, and modules, all cache keys must adhere to the following deterministic format:

```text
{module}_{entity_or_action}_{company_id}_{unique_identifier}_{optional_date}
```

### Examples
* **Dashboard KPI Stats**: `dashboard_kpi_{company_id}_{date_Y-m-d}` (e.g., `dashboard_kpi_1_2026-07-27`)
* **Dashboard Deadlines**: `dashboard_deadlines_{company_id}_{date_Y-m-d}`
* **Product Catalog Search**: `product_list_{company_id}_md5_{filters_hash}_p_{page}`
* **System Setting**: `setting_{key}_comp_{company_id}`
* **Brand Profile**: `brand_profile_{brand_id}`

---

## 2. Tag Convention & Taxonomy

SunTrack uses Redis cache tagging to allow bulk invalidation of related data sets without clearing the entire Redis database.

| Tag Name | Description | Affected Modules / Queries |
| :--- | :--- | :--- |
| `dashboard` | Global dashboard aggregations | KPI Cards, Deadline lists, Activity feeds |
| `campaigns` | Campaign entity data | Campaign lists, PIC filters, Detail pages |
| `promotions` | Promotion entity data | Promotion lists, Campaign promo maps, Pricing |
| `products` | Product catalog items | Product lists, SKU lookups, Search results |
| `variants` | Variant pricing and stock | Variant listings, Price snapshots, Margin checks |
| `catalog` | Combined Product & Variant data | E-commerce catalog view, Brand review views |
| `settings` | System and tenant configuration | App name, UI theme, Currency settings |
| `company_{id}` | Tenant-specific boundary tag | All cached items belonging to Company ID `{id}` |

---

## 3. TTL (Time-To-Live) Policy

Cache duration is assigned based on data volatility and access frequency:

| Data Category | Assigned TTL | Rationale |
| :--- | :--- | :--- |
| **Real-Time Operational (Dashboard Stats)** | **300 seconds (5 mins)** | balances near-instant page loads with real-time operational accuracy. Auto-invalidated on entity change. |
| **Catalog Listings (Products & Variants)** | **600 seconds (10 mins)** | High-read, low-write frequency. Auto-invalidated via model observers when catalog items change. |
| **System Settings & Currency Configuration** | **Forever (`rememberForever`)** | Zero volatility during normal operations. Invalidated immediately upon admin update via `SystemSettingObserver`. |
| **Token Verification & Session Cache** | **3600 seconds (1 hour)** | Aligned with authentication token lifetime and OAuth security guidelines. |
| **Export / Analytics Report Snapshots** | **86400 seconds (24 hours)** | Historical report generation is expensive; once generated, snapshots remain immutable for 24 hours. |

---

## 4. Automated Cache Invalidation Flow

SunTrack strictly forbids manual cache clearing in business logic. All cache invalidation is automated through **Eloquent Model Observers** registered in `AppServiceProvider`:

```mermaid
graph TD
    A[Admin/Brand Action] -->|Create / Update / Delete| B(Eloquent Model Event)
    B -->|saved() / deleted()| C{Model Observer}
    C -->|CampaignObserver| D[CacheService::flushTags: 'dashboard', 'campaigns']
    C -->|PromotionObserver| E[CacheService::flushTags: 'dashboard', 'promotions']
    C -->|ProductObserver| F[CacheService::flushTags: 'dashboard', 'products', 'catalog']
    C -->|VariantObserver| G[CacheService::flushTags: 'dashboard', 'variants', 'catalog']
    C -->|SystemSettingObserver| H[CacheService::flushTags: 'settings']
    D & E & F & G & H -->|Redis UNLINK/DEL| I[(Redis Cache Store)]
```

### Invalidation Rules
1. **Never flush `Cache::flush()` globally** in production; always target specific tag bundles using `CacheService->flushTags(...)`.
2. **Cascading Invalidation**: Modifying a child entity (e.g., `Variant`) must invalidate both parent tags (`products`, `catalog`) and top-level operational tags (`dashboard`).

---

## 5. Cache Warming Strategy

To prevent cold-start latency spikes after deployments or scheduled Redis maintenance, SunTrack implements an automated cache warming protocol:

### 5.1. Automated Warming via Cron Scheduler
The Laravel task scheduler triggers warming routines daily at 00:01 AM (after log rotation and date transitions):
* Warms Dashboard KPI cards for all active tenant companies for the new calendar day.
* Pre-fetches system settings and brand configurations into memory.

### 5.2. Post-Deployment Warming Command
In Docker production environments, deploy scripts execute cache warming immediately after running database migrations:

```bash
# Clear stale compiled views and configuration
docker compose exec app php artisan optimize:clear

# Rebuild configuration and route cache
docker compose exec app php artisan optimize

# Execute custom benchmark seeder / warmer if required
docker compose exec app php artisan suntrack:benchmark-run --json
```

---

## 6. Observability & Hit/Miss Monitoring

All cache interactions are monitored via `App\Services\Monitoring\MetricsService`:
* Every `remember()` invocation records a hit or miss event.
* Real-time hit/miss ratios can be retrieved programmatically via `MetricsService::getCacheHitMissRatio()` or exported in Prometheus format (`suntrack_cache_hit_ratio`).
* **SLA Threshold**: Production systems must maintain a cache hit ratio $\ge \mathbf{85\%}$ during normal business hours.
