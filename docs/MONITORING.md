# System Monitoring Dashboard — SunTrack

> **Sprint 11 · Enterprise Observability & Health Monitoring**

---

## Table of Contents

1. [Overview](#overview)
2. [Monitoring Categories](#monitoring-categories)
3. [MetricsService Architecture](#metricsservice-architecture)
4. [API Endpoints](#api-endpoints)
5. [Prometheus Metrics Export](#prometheus-metrics-export)
6. [SLA: Cache Hit Ratio](#sla-cache-hit-ratio)
7. [Slow Query Logging](#slow-query-logging)
8. [Docker Usage](#docker-usage)
9. [Future Integrations](#future-integrations)

---

## Overview

The **SunTrack Monitoring Dashboard** provides a comprehensive, real-time view of system health across all critical infrastructure layers. Introduced in Sprint 11, the monitoring subsystem is built around the `MetricsService` abstraction layer, which aggregates telemetry data from seven distinct categories and exposes it through a unified REST API.

Design principles:
- **Single pane of glass**: one dashboard surfaces metrics for application, database, cache, queues, scheduler, storage, and containers.
- **Prometheus-compatible**: all key metrics are exportable in OpenMetrics format for external aggregation.
- **Non-intrusive**: telemetry collection adds no measurable overhead to request handling.

---

## Monitoring Categories

The dashboard is organized into **7 categories**, each mapped to a dedicated service section:

| # | Category | Metrics Captured | Data Source |
|---|---|---|---|
| 1 | **Application** | Request rate, avg latency, error rate, memory usage | Laravel middleware telemetry |
| 2 | **Database** | Query count, slow queries, connection pool, table sizes | `DB::listen`, `information_schema` |
| 3 | **Redis** | Hit ratio, miss ratio, connected clients, memory used | `redis-cli INFO` / Predis stats |
| 4 | **Queue** | Pending jobs, failed jobs, processing jobs, throughput | `jobs` + `failed_jobs` tables |
| 5 | **Scheduler** | Last run timestamps, next run, missed runs | `schedule_monitor` / `cache` keys |
| 6 | **Storage** | Disk used, disk free, log file sizes, upload directory size | PHP `disk_*` functions |
| 7 | **Docker** | Container status, CPU usage, memory usage | Docker Engine API / `docker stats` |

---

## MetricsService Architecture

All metric aggregation is centralized in the `MetricsService` abstraction layer:

```
app/Services/Monitoring/
├── MetricsService.php            ← Orchestrator; delegates to collectors
├── Collectors/
│   ├── ApplicationMetricsCollector.php
│   ├── DatabaseMetricsCollector.php
│   ├── RedisMetricsCollector.php
│   ├── QueueMetricsCollector.php
│   ├── SchedulerMetricsCollector.php
│   ├── StorageMetricsCollector.php
│   └── DockerMetricsCollector.php
└── Formatters/
    └── PrometheusFormatter.php   ← Converts array metrics to OpenMetrics text format
```

### MetricsService Interface

```php
// app/Services/Monitoring/MetricsService.php

namespace App\Services\Monitoring;

class MetricsService
{
    /**
     * Collect health summary across all categories.
     * @return array{status: string, categories: array}
     */
    public function getHealthSummary(): array;

    /** @return array Queue depth and failure stats */
    public function getQueueStats(): array;

    /** @return array Redis hit/miss ratios and memory */
    public function getCacheStats(): array;

    /** @return array Disk usage and file counts */
    public function getStorageStats(): array;

    /** @return array Slow queries, pool stats, table sizes */
    public function getDatabaseStats(): array;

    /** @return string Prometheus OpenMetrics format */
    public function getPrometheusMetrics(): string;
}
```

Each collector implements a `collect(): array` method and is registered as a singleton in `MonitoringServiceProvider`.

---

## API Endpoints

All monitoring endpoints require admin authentication (`role:admin`) and are scoped under:

```
Base: /api/v1/admin/system
```

### Endpoint Summary

| Endpoint | Method | Description | Cache TTL |
|---|---|---|---|
| `/health` | `GET` | Full system health summary (all 7 categories) | 30 s |
| `/queue-stats` | `GET` | Queue depth, failed jobs, throughput | 15 s |
| `/cache-stats` | `GET` | Redis hit ratio, memory, connected clients | 30 s |
| `/storage-stats` | `GET` | Disk usage, log sizes, upload directory | 60 s |
| `/db-stats` | `GET` | Slow queries, pool status, table row counts | 60 s |
| `/metrics` | `GET` | Prometheus-compatible OpenMetrics export | No cache |

### GET `/api/v1/admin/system/health`

```json
{
  "status": "healthy",
  "checked_at": "2025-11-14T08:30:00Z",
  "categories": {
    "application": {
      "status": "healthy",
      "requests_per_minute": 142,
      "avg_latency_ms": 87,
      "error_rate_pct": 0.4,
      "memory_usage_mb": 128
    },
    "database": {
      "status": "healthy",
      "slow_queries_last_hour": 3,
      "active_connections": 8,
      "max_connections": 100
    },
    "redis": {
      "status": "healthy",
      "hit_ratio_pct": 91.4,
      "memory_used_mb": 64,
      "connected_clients": 12
    },
    "queue": {
      "status": "warning",
      "pending_jobs": 47,
      "failed_jobs": 2,
      "processing_jobs": 5
    },
    "scheduler": {
      "status": "healthy",
      "last_heartbeat": "2025-11-14T08:29:00Z",
      "missed_runs": 0
    },
    "storage": {
      "status": "healthy",
      "disk_used_gb": 12.4,
      "disk_free_gb": 87.6,
      "log_size_mb": 34.2
    },
    "docker": {
      "status": "healthy",
      "containers_running": 6,
      "containers_stopped": 0
    }
  }
}
```

**Status values:**

| Value | Meaning |
|---|---|
| `healthy` | All metrics within acceptable thresholds |
| `warning` | One or more metrics approaching threshold |
| `critical` | One or more metrics exceeded threshold |
| `unknown` | Collector returned no data (check logs) |

### GET `/api/v1/admin/system/queue-stats`

```json
{
  "pending_jobs": 47,
  "processing_jobs": 5,
  "failed_jobs": 2,
  "completed_last_hour": 894,
  "throughput_per_minute": 14.9,
  "oldest_pending_age_seconds": 312,
  "queues": {
    "default": { "pending": 22, "failed": 1 },
    "notifications": { "pending": 18, "failed": 0 },
    "exports": { "pending": 7, "failed": 1 }
  }
}
```

### GET `/api/v1/admin/system/cache-stats`

```json
{
  "driver": "redis",
  "hit_ratio_pct": 91.4,
  "hits": 18240,
  "misses": 1713,
  "memory_used_mb": 64.3,
  "memory_peak_mb": 72.1,
  "connected_clients": 12,
  "keyspace": {
    "total_keys": 4821,
    "expires": 4800
  }
}
```

---

## Prometheus Metrics Export

### GET `/api/v1/admin/system/metrics`

Returns metrics in **OpenMetrics / Prometheus text format** (`Content-Type: text/plain; version=0.0.4`).

```
# HELP suntrack_api_requests_total Total number of API requests handled.
# TYPE suntrack_api_requests_total counter
suntrack_api_requests_total{method="GET",status="200"} 18472
suntrack_api_requests_total{method="POST",status="201"} 3291
suntrack_api_requests_total{method="POST",status="422"} 184

# HELP suntrack_api_latency_avg_ms Average API response time in milliseconds.
# TYPE suntrack_api_latency_avg_ms gauge
suntrack_api_latency_avg_ms{route="search"} 12.4
suntrack_api_latency_avg_ms{route="promotions.index"} 87.2
suntrack_api_latency_avg_ms{route="analytics.pricing"} 143.7

# HELP suntrack_cache_hit_ratio Redis cache hit ratio (0–100).
# TYPE suntrack_cache_hit_ratio gauge
suntrack_cache_hit_ratio 91.4

# HELP suntrack_memory_usage_mb PHP process memory usage in megabytes.
# TYPE suntrack_memory_usage_mb gauge
suntrack_memory_usage_mb 128.6

# HELP suntrack_queue_pending_jobs Number of jobs waiting to be processed.
# TYPE suntrack_queue_pending_jobs gauge
suntrack_queue_pending_jobs{queue="default"} 22
suntrack_queue_pending_jobs{queue="notifications"} 18
suntrack_queue_pending_jobs{queue="exports"} 7

# HELP suntrack_queue_failed_jobs Total number of failed jobs.
# TYPE suntrack_queue_failed_jobs counter
suntrack_queue_failed_jobs 2
```

### Scraping with Prometheus

Add the following job to your `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'suntrack'
    scrape_interval: 30s
    bearer_token: '<admin_api_token>'
    static_configs:
      - targets: ['suntrack-app:80']
    metrics_path: /api/v1/admin/system/metrics
```

---

## SLA: Cache Hit Ratio

The system enforces a **Cache Hit Ratio SLA** to ensure Redis is being utilized effectively:

| Period | Minimum Required Hit Ratio |
|---|---|
| Business hours (08:00–18:00 local) | **≥ 85%** |
| Off-peak hours | ≥ 70% |

When the ratio drops below the SLA threshold during business hours, a `warning` status is set on the `redis` category and an alert is dispatched to the configured notification channel.

```php
// app/Services/Monitoring/Collectors/RedisMetricsCollector.php

$hitRatio = ($hits / max($hits + $misses, 1)) * 100;

$status = match (true) {
    $this->isBusinessHours() && $hitRatio < 85 => 'warning',
    $hitRatio < 60                             => 'critical',
    default                                    => 'healthy',
};
```

---

## Slow Query Logging

Queries that exceed **100 ms** are automatically logged via Laravel's `DB::listen` hook, registered in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php

DB::listen(function (QueryExecuted $query) {
    if ($query->time > 100) {
        Log::channel('slow_queries')->warning('Slow query detected', [
            'sql'      => $query->sql,
            'bindings' => $query->bindings,
            'time_ms'  => $query->time,
            'caller'   => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);
    }
});
```

Slow query logs are written to `storage/logs/slow_queries.log` and are surfaced in the `/db-stats` endpoint under `slow_queries_last_hour`.

---

## Docker Usage

Check individual monitoring endpoints from within the container:

```bash
# Full health summary
docker compose exec app curl -s \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  http://localhost/api/v1/admin/system/health | jq .

# Queue stats
docker compose exec app curl -s \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  http://localhost/api/v1/admin/system/queue-stats | jq .

# Redis cache stats
docker compose exec app curl -s \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  http://localhost/api/v1/admin/system/cache-stats | jq .

# Prometheus metrics (raw text)
docker compose exec app curl -s \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  http://localhost/api/v1/admin/system/metrics

# Tail slow query log
docker compose exec app tail -f storage/logs/slow_queries.log
```

---

## Future Integrations

The monitoring subsystem is architected to accommodate the following planned integrations:

| Integration | Status | Description |
|---|---|---|
| **Grafana Dashboard** | Planned | Pre-built dashboard JSON for all `suntrack_*` Prometheus metrics |
| **OpenTelemetry** | Planned | Distributed tracing via OTLP exporter; trace propagation across services |
| **Laravel Pulse** | Planned | Native Laravel dashboard for request breakdown, exception tracking, and slow jobs |
| **PagerDuty / OpsGenie** | Planned | Alert routing when category status transitions to `critical` |
| **Alertmanager** | Planned | Rule-based alerting via Prometheus Alertmanager for SLA breaches |

> **Architecture note:** The `MetricsService` abstraction is designed so that adding OpenTelemetry requires only a new `OtelExporter` class — no changes to collectors or API controllers.

---

*Last updated: Sprint 11 · SunTrack Platform*
