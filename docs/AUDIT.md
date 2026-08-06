# Enterprise Audit Dashboard — SunTrack

> **Sprint 11 · Audit Logging, History & Retention Management**

---

## Table of Contents

1. [Overview](#overview)
2. [Data Sources](#data-sources)
   - [Login History](#login-history)
   - [Queue History](#queue-history)
   - [Error Logs](#error-logs)
   - [Audit Summary KPI](#audit-summary-kpi)
3. [Retention Policy](#retention-policy)
4. [AuditRepository](#auditrepository)
5. [API Endpoints](#api-endpoints)
6. [Filters Reference](#filters-reference)
7. [Architecture Notes](#architecture-notes)

---

## Overview

The **Enterprise Audit Dashboard** provides administrators with a centralized audit trail across four key observability surfaces: authentication events, background job history, application error logs, and aggregate KPIs.

Introduced in Sprint 11, the audit system is built around:

- **AuditRepository** — all data access is encapsulated; controllers contain no raw SQL.
- **Configurable retention** — retention periods are managed via `system_settings`, not code.
- **60-second caching** — per-hour cache buckets prevent redundant DB reads during high-traffic review sessions.

---

## Data Sources

### Login History

**Source table:** `login_histories`

Records every authentication attempt — successful or failed — along with contextual metadata.

#### Schema (relevant columns)

| Column | Type | Description |
|---|---|---|
| `id` | `bigint` | Primary key |
| `user_id` | `bigint` | FK to `users` (nullable for failed attempts) |
| `ip_address` | `varchar(45)` | Client IP (supports IPv6) |
| `user_agent` | `text` | Browser/client user-agent string |
| `status` | `enum` | `success` or `failed` |
| `failure_reason` | `varchar` | e.g., `invalid_password`, `account_locked` |
| `created_at` | `timestamp` | Attempt timestamp |

#### Supported Filters

- `status` — `success` or `failed`
- `ip_address` — exact match
- `user_id` — specific user
- `date_from` / `date_to` — date range

---

### Queue History

**Source tables:** `jobs` (pending/processing) + `failed_jobs` (failed)

Provides visibility into background job execution state.

| State | Source Table | Meaning |
|---|---|---|
| `pending` | `jobs` | Queued but not yet started |
| `processing` | `jobs` | Currently being executed by a worker |
| `failed` | `failed_jobs` | Execution ended with an exception |

#### Failed Job Record (example)

```json
{
  "id": 1482,
  "queue": "notifications",
  "payload_class": "App\\Jobs\\SendApprovalNotification",
  "failed_at": "2025-11-13T22:14:08Z",
  "exception": "Connection refused: mail.example.com:587",
  "attempts": 3
}
```

#### Retry a Failed Job

```bash
# Retry a single failed job by ID
docker compose exec app php artisan queue:retry 1482

# Retry all failed jobs
docker compose exec app php artisan queue:retry all

# Flush all failed jobs (irreversible)
docker compose exec app php artisan queue:flush
```

---

### Error Logs

**Source file:** `storage/logs/laravel.log`

The error log viewer streams the **last N lines** of the Laravel application log directly to the admin dashboard, providing quick visibility into recent exceptions and warnings without requiring SSH access.

| Property | Value |
|---|---|
| Source | `storage/logs/laravel.log` |
| Default lines returned | Last **100 lines** |
| Max lines per request | 500 |
| Log format | Laravel default (Monolog) |
| Sensitive data | Scrubbed — stack traces truncated at 20 frames |

#### Sample Response — Last 3 lines

```json
{
  "data": {
    "lines": [
      {
        "timestamp": "2025-11-14 08:31:02",
        "level": "ERROR",
        "message": "SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded",
        "channel": "local",
        "context": {}
      },
      {
        "timestamp": "2025-11-14 08:31:45",
        "level": "WARNING",
        "message": "Cache driver [redis] unreachable, falling back to array driver",
        "channel": "local",
        "context": {}
      },
      {
        "timestamp": "2025-11-14 08:32:11",
        "level": "INFO",
        "message": "Scheduled task [CleanupExpiredTokens] completed in 0.34s",
        "channel": "local",
        "context": {}
      }
    ],
    "total_lines_returned": 3,
    "log_size_bytes": 35913728
  }
}
```

---

### Audit Summary KPI

The `/summary` endpoint provides a single-request snapshot of key audit KPIs, designed for the admin dashboard home card.

| KPI | Description |
|---|---|
| `logins_today` | Total authentication attempts since midnight (local timezone) |
| `failed_logins_today` | Failed authentication attempts since midnight |
| `failed_jobs` | Current count of records in `failed_jobs` table |
| `pending_jobs` | Current count of records in `jobs` table with `reserved_at = NULL` |
| `error_log_size_mb` | Size of `storage/logs/laravel.log` in megabytes |

#### Sample Response — `GET /api/v1/admin/audit/summary`

```json
{
  "data": {
    "logins_today": 347,
    "failed_logins_today": 12,
    "failed_jobs": 2,
    "pending_jobs": 47,
    "error_log_size_mb": 34.3
  },
  "meta": {
    "cached_until": "2025-11-14T09:01:00Z"
  }
}
```

---

## Retention Policy

Audit data retention is governed by **configurable settings** stored in the `system_settings` table. Administrators can adjust retention windows directly from the Admin UI — no code deployment required.

### Configuration Keys

| Setting Key | Default Value | Description |
|---|---|---|
| `audit.retention.login_history_days` | **90 days** | Days to retain `login_histories` records |
| `audit.retention.notification_history_days` | **30 days** | Days to retain `notification_histories` records |
| `audit.retention.queue_history_days` | **7 days** | Days to retain `failed_jobs` records |

### Recommended Defaults

| Data Type | Recommended Retention | Rationale |
|---|---|---|
| Login History | **90 days** | Security auditing; covers typical incident response windows |
| Notification History | **30 days** | Operational debugging; notification data ages quickly |
| Queue History | **7 days** | Failed jobs are either retried or require immediate attention |

### How Retention is Applied

A scheduled Artisan command runs nightly at **01:00** to prune records beyond the configured retention window:

```bash
# Manual execution (for testing or backfill)
docker compose exec app php artisan audit:prune

# Verify settings without deleting (dry run)
docker compose exec app php artisan audit:prune --dry-run
```

The command reads retention values from `system_settings` at runtime, so changes take effect on the next scheduled run without any application restart.

```php
// app/Console/Commands/PruneAuditData.php (excerpt)

$loginRetention = SystemSetting::get('audit.retention.login_history_days', 90);
LoginHistory::where('created_at', '<', now()->subDays($loginRetention))->delete();
```

---

## AuditRepository

```
app/Repositories/Audit/AuditRepository.php
```

All data access for the audit dashboard is encapsulated in `AuditRepository`. This enforces consistent caching and ensures controllers remain thin.

```php
namespace App\Repositories\Audit;

interface AuditRepositoryInterface
{
    /** @return array Paginated login history with filters applied */
    public function getLoginHistory(int $companyId, array $filters = []): array;

    /** @return array Paginated queue history (pending + failed) */
    public function getQueueHistory(int $companyId, array $filters = []): array;

    /** @return array Last N lines of the error log */
    public function getErrorLogs(int $n = 100): array;

    /** @return array KPI summary for the dashboard card */
    public function getSummary(int $companyId): array;
}
```

### Caching Strategy

Results are cached using a **per-hour bucket** key to balance freshness with database load:

```php
// Cache key: audit:{companyId}:{report}:{hourBucket}
// e.g.: audit:42:login_history:2025111409

$hourBucket = now()->format('YmdH');
$cacheKey   = "audit:{$companyId}:{$report}:{$hourBucket}";

return Cache::remember($cacheKey, 60, fn () => $this->query($filters));
```

| Property | Value |
|---|---|
| Cache TTL | **60 seconds** |
| Cache Key | `audit:{companyId}:{report}:{hourBucket}` |
| Hour bucket | `YmdH` format (e.g., `2025111409` = Nov 14 2025, 09:xx) |

---

## API Endpoints

All audit endpoints require admin authentication and are scoped to the authenticated company.

| Endpoint | Method | Description |
|---|---|---|
| `/api/v1/admin/audit/login-history` | `GET` | Paginated login attempt history |
| `/api/v1/admin/audit/queue-history` | `GET` | Pending, processing, and failed job history |
| `/api/v1/admin/audit/error-logs` | `GET` | Last N lines of the application error log |
| `/api/v1/admin/audit/summary` | `GET` | Aggregate KPI dashboard card |

### Pagination

All list endpoints support standard Laravel pagination:

```
GET /api/v1/admin/audit/login-history?page=2&per_page=25
```

| Parameter | Default | Max |
|---|---|---|
| `page` | `1` | — |
| `per_page` | `20` | `100` |

---

## Filters Reference

### Login History Filters

| Filter | Type | Example | Description |
|---|---|---|---|
| `status` | `string` | `failed` | Filter by `success` or `failed` |
| `ip_address` | `string` | `192.168.1.10` | Exact IP address match |
| `user_id` | `integer` | `42` | Filter by specific user |
| `date_from` | `date` | `2025-11-01` | Start of date range (`Y-m-d`) |
| `date_to` | `date` | `2025-11-14` | End of date range (`Y-m-d`) |

### Queue History Filters

| Filter | Type | Example | Description |
|---|---|---|---|
| `status` | `string` | `failed` | Filter by `pending`, `processing`, or `failed` |
| `queue` | `string` | `notifications` | Filter by queue name |
| `date_from` | `date` | `2025-11-01` | Start of date range |
| `date_to` | `date` | `2025-11-14` | End of date range |

---

## Architecture Notes

- **No raw SQL in controllers**: All DB interaction flows through `AuditRepository`. Any new audit data source must be added to the repository, not the controller.
- **Read-only API**: All audit endpoints are `GET`-only. Mutations (e.g., retrying jobs) are handled by separate queue management commands.
- **Retention is configuration, not code**: Retention thresholds live in `system_settings`. Changing a threshold requires no deployment.
- **Error log access**: The error log reader uses PHP's `SplFileObject` with reverse iteration for efficiency — it never loads the entire log file into memory.

---

*Last updated: Sprint 11 · SunTrack Platform*
