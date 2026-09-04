# SunTrack Database Performance & Scaling Architecture (ADR-022)

This document defines SunTrack's database performance strategy, covering indexes, query optimization patterns, and scaling preparation. PostgreSQL 18 is the production database. MySQL remains covered for compatibility in CI.

---

## 1. Index Registry

All production indexes are managed via dedicated migrations. The following table provides a full registry of every index deployed to production.

### 1.1. Core Table Indexes

| Table | Index Name | Columns | Type | Migration File |
| :--- | :--- | :--- | :--- | :--- |
| `campaigns` | `camp_brand_status_idx` | `(brand_id, status)` | Composite | `2026_07_27_130001` |
| `campaigns` | `camp_brand_end_idx` | `(brand_id, end_date)` | Composite | `2026_07_27_130001` |
| `promotions` | `prom_camp_status_idx` | `(campaign_id, status)` | Composite | `2026_07_27_130001` |
| `promotions` | `prom_status_end_idx` | `(status, end_date)` | Composite | `2026_07_27_130001` |
| `products` | `prod_brand_status_idx` | `(brand_id, status)` | Composite | `2026_07_27_130001` |
| `products` | `prod_sku_idx` | `(sku)` | Single | `2026_07_27_130001` |
| `variants` | `var_prod_sku_idx` | `(product_id, sku)` | Composite | `2026_07_27_130001` |
| `secure_links` | `sec_revoked_exp_idx` | `(revoked_at, expires_at)` | Composite | `2026_07_27_130001` |
| `approval_histories` | `appr_new_status_idx` | `(new_status)` | Single | `2026_07_27_130001` |
| `activity_logs` | `act_created_at_idx` | `(created_at)` | Single | `2026_07_27_130001` |
| `promotions` | Unique on `code` | `(code)` | Unique | `2026_07_26_002408` |
| `products` | Unique on `(brand_id, code)` | `(brand_id, code)` | Unique Composite | `2026_07_25_161209` |
| `variants` | Unique on `(product_id, code)` | `(product_id, code)` | Unique Composite | `2026_07_25_161210` |
| `secure_links` | Unique on `token` | `(token)` | Unique | `2026_07_25_161212` |

---

## 2. Composite Index Design Rationale

### `campaigns(brand_id, status)`
**Rationale**: The Dashboard KPI card for "Active Campaigns" and the Campaign listing endpoint always filter by `brand_id` first (multitenant scoping via brand) and subsequently narrow by `status`. The composite index satisfies both predicates in a single index scan, reducing full-table scan risk at scale.

**Queries utilizing this index:**
```sql
SELECT * FROM campaigns
WHERE brand_id = ?
  AND status = 'Running'
ORDER BY end_date ASC
LIMIT 15;
```

### `campaigns(brand_id, end_date)`
**Rationale**: The Deadline Monitoring dashboard queries campaigns approaching their `end_date` within a date range, always scoped to a brand. This index allows MySQL to use an efficient range scan on `end_date` after filtering `brand_id`.

**Queries utilizing this index:**
```sql
SELECT * FROM campaigns
WHERE brand_id = ?
  AND end_date BETWEEN '2026-07-27' AND '2026-08-03';
```

### `promotions(campaign_id, status)`
**Rationale**: The Promotion listing endpoint and the Campaign detail page both join promotions filtered by `campaign_id` and optionally by `status`. This composite index eliminates expensive full-scan of the promotions table on campaign-scoped queries.

### `promotions(status, end_date)`
**Rationale**: The Dashboard Pending Approval aggregation and deadline monitoring both filter promotions by their `status` column and sort or range-filter by `end_date`. The index eliminates file-sort operations on result sets.

### `products(brand_id, status)`
**Rationale**: The Product Catalog API always queries products scoped to a brand with an optional status filter. The composite index powers both filtered and unfiltered brand-scoped product lookups without a full-table scan.

### `variants(product_id, sku)`
**Rationale**: The Variant listing and individual SKU lookup endpoints combine `product_id` and `sku`. The composite index allows single index range scans for both variant listing and exact-match SKU validation.

### `secure_links(revoked_at, expires_at)`
**Rationale**: The Dashboard Expiring Links section queries for secure links that have a `NULL` `revoked_at` (meaning they are active) and have an `expires_at` within the next 7 days. The composite index supports this `NULL`-filtered range scan efficiently.

### `activity_logs(created_at)`
**Rationale**: Activity log queries are always ordered or filtered by recency. The `created_at` index avoids full-table sort operations on the largest-growth table in the system.

---

## 3. Read/Write Replica Separation Strategy

SunTrack's `config/database.php` is configured with a **sticky read/write split** architecture:

```php
'mysql' => [
    'read'   => ['host' => explode(',', env('DB_READ_HOST', env('DB_HOST', 'mysql')))],
    'write'  => ['host' => [env('DB_WRITE_HOST', env('DB_HOST', 'mysql'))]],
    'sticky' => true,
    // ... other connection config
],
```

### How `sticky => true` Works
When a write operation (`INSERT`, `UPDATE`, `DELETE`) is performed within a single request lifecycle, all subsequent **read queries in that same request** are automatically directed to the **write (primary) master**, not the read replica. This eliminates the replication lag race condition where a resource just created by the current request may not yet appear on the replica.

### Environment Configuration

| Environment Variable | Purpose | Default |
| :--- | :--- | :--- |
| `DB_HOST` | Master write host (also used as read host if replicas not configured) | `mysql` (Docker alias) |
| `DB_WRITE_HOST` | Explicit primary master host override | Falls back to `DB_HOST` |
| `DB_READ_HOST` | Comma-separated read replica hosts | Falls back to `DB_HOST` |

### Local Development (Single Host)
In Docker Compose local development, `DB_HOST=mysql` is set without `DB_READ_HOST` or `DB_WRITE_HOST`. Laravel will automatically route all reads and writes to the same `mysql` container. No configuration change is required for read-replica behavior to be enabled when a replica is provisioned.

---

## 4. Database Scaling Strategy

### Phase 1: Indexing & Query Optimization (✅ Complete — Sprint 10)
- Composite indexes on all high-frequency `WHERE`/`JOIN`/`ORDER BY` patterns.
- Repository Pattern guarantees all list queries use eager loading (no N+1 growth).
- Slow query logging threshold at 100ms via `DB::listen` in `AppServiceProvider`.

### Phase 2: Read Replica Scaling (Prepared — Sprint 10)
- MySQL read/write split is configured and ready in `config/database.php`.
- Add `DB_READ_HOST=replica1.db.internal,replica2.db.internal` to production `.env` to activate.
- No application code changes required.

### Phase 3: Horizontal Table Partitioning (Future — Sprint 12+)
- `activity_logs` is the highest-growth table; partition by `YEAR(created_at)` when rows exceed 10M.
- `approval_histories` and `comments` follow the same growth profile.
- Partitioning can be implemented as a new migration without breaking existing queries.

### Phase 4: Query Cache & Connection Pooling (Future)
- ProxySQL or PgBouncer for MySQL connection pooling at 1000+ concurrent users.
- Redis-backed query result caching for expensive aggregations (already prepared via `CacheService`).

---

## 5. Naming Conventions for Future Indexes

All indexes must adhere to the following naming pattern:

```
{table_abbreviation}_{column_abbreviation(s)}_idx
```

Examples:
- `campaigns` + `brand_id, status` → `camp_brand_status_idx`
- `activity_logs` + `created_at` → `act_created_at_idx`

Foreign key constraints retain Laravel's auto-generated naming convention unless explicitly renamed for readability.
