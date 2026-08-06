# SunTrack Performance Benchmarking & SLA Documentation (ADR-024)

This document defines SunTrack's enterprise performance standards, benchmark procedures, SLA targets, and regression testing protocol for all production deployments.

---

## 1. Overview

SunTrack targets enterprise-grade API response latency across all operational modules. Performance benchmarks must be executed before every sprint closure and before every production release. All results must be recorded and compared against historical baselines to detect regressions.

---

## 2. Environment Requirements

All benchmarks must be executed inside the Docker environment with production-equivalent services running:

```bash
# Verify all services are healthy
docker compose ps

# Expected healthy services:
# - app (Laravel PHP-FPM)
# - nginx
# - mysql
# - redis
# - queue-worker
# - scheduler
```

---

## 3. Benchmark Dataset Generation

SunTrack uses the `suntrack:benchmark-seed` Artisan command to generate high-volume realistic datasets using chunked batch inserts (memory-efficient, no risk of PHP OOM crash).

### Command Syntax

```bash
docker compose exec app php artisan suntrack:benchmark-seed \
    --count=100000 \
    --chunk=2500
```

### Options

| Option | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `--count` | `int` | `100000` | Total records to seed across all core tables |
| `--chunk` | `int` | `2500` | Batch insert chunk size per `DB::insert()` transaction |

### Dataset Distribution

When `--count=100000` is used, records are distributed as follows:

| Table | Approximate Count | Data Characteristics |
| :--- | :--- | :--- |
| `products` | ~20,000 | Status rotates `Active/Inactive` (1-in-10 inactive) |
| `variants` | ~20,000 | Linked to first 5000 products round-robin; realistic pricing |
| `campaigns` | ~20,000 | Status rotates `Draft/Running/Completed/Cancelled` |
| `promotions` | ~20,000 | Status rotates `Pending/Approved/Rejected/Partially Approved` |
| `activity_logs` | ~20,000 | Covers all 4 action types; linked to seeded campaigns |

### Estimated Seeding Duration (Docker MySQL)

| Record Count | Chunk Size | Approximate Duration |
| :--- | :--- | :--- |
| 500 | 100 | ~0.24 seconds |
| 10,000 | 1,000 | ~2–4 seconds |
| 100,000 | 2,500 | ~15–30 seconds |

---

## 4. Running the Performance Benchmark

SunTrack uses the `suntrack:benchmark-run` Artisan command to measure real-world query latency against production-representative data.

### Command Syntax

```bash
# Standard tabular output
docker compose exec app php artisan suntrack:benchmark-run

# JSON output to stdout
docker compose exec app php artisan suntrack:benchmark-run --json

# JSON output saved to file (for CI/CD artifact)
docker compose exec app php artisan suntrack:benchmark-run \
    --json \
    --output=storage/logs/benchmark_results.json
```

### Options

| Option | Description |
| :--- | :--- |
| `--json` | Emit JSON output to stdout alongside the table |
| `--output={path}` | Save JSON results to a file for archival/CI artifact upload |

---

## 5. SLA Targets

| Benchmark Test Case | SLA Target | Escalation Threshold | Severity if Exceeded |
| :--- | :--- | :--- | :--- |
| **Dashboard Stats — Cold Cache (SQL Aggregation)** | $\le$ 100 ms | $\le$ 200 ms | 🔴 Critical if > 200 ms |
| **Dashboard Stats — Warm Redis Cache** | $\le$ 15 ms | $\le$ 30 ms | 🔴 Critical if > 30 ms |
| **Product Catalog Search (SKU Indexed Lookup)** | $\le$ 50 ms | $\le$ 100 ms | 🟡 Warning if > 50 ms |
| **Promotion Listing (Eager Load)** | $\le$ 50 ms | $\le$ 100 ms | 🟡 Warning if > 50 ms |
| **Campaign Listing (Company Scoped)** | $\le$ 50 ms | $\le$ 100 ms | 🟡 Warning if > 50 ms |

---

## 6. Latest Benchmark Results (Sprint 10 — 2026-07-27)

Benchmark executed against a dataset of **500 seeded records** per table (local Docker environment):

| Benchmark Test Case | Measured Latency | Target SLA | Status |
| :--- | :--- | :--- | :--- |
| Dashboard Operational Stats (Cold Cache / SQL Aggregation) | **23.07 ms** | $\le$ 100.0 ms | 🟢 PASS |
| Dashboard Operational Stats (Warm Redis Tag Cache) | **1.91 ms** | $\le$ 15.0 ms | 🟢 PASS |
| Product Catalog Search (Indexed SKU/Code Lookup + Variant Count) | **13.84 ms** | $\le$ 50.0 ms | 🟢 PASS |
| Promotion Listing (Eager Load Campaign/Brand + Variant Count) | **14.82 ms** | $\le$ 50.0 ms | 🟢 PASS |
| Campaign Listing (Eager Load PIC + Company Scoping) | **11.30 ms** | $\le$ 50.0 ms | 🟢 PASS |

* **Average System Latency:** `12.99 ms`
* **All SLA Targets:** ✅ Passed (100%)
* **Redis Cache Effectiveness:** Cold → Warm ratio = `23.07 ms → 1.91 ms` (**92% latency reduction**)

---

## 7. Regression Benchmark Protocol

Before every sprint closure, the following regression benchmark procedure must be executed:

### Step 1: Seed Fresh Benchmark Data
```bash
docker compose exec app php artisan suntrack:benchmark-seed --count=100000 --chunk=2500
```

### Step 2: Run Full Test Suite (Must Pass 100%)
```bash
docker compose exec app php artisan test
```

### Step 3: Execute Benchmark and Save Results
```bash
docker compose exec app php artisan suntrack:benchmark-run \
    --json \
    --output=storage/logs/benchmark_$(date +%Y%m%d_%H%M%S).json
```

### Step 4: Compare Against Baseline

Any sprint that introduces latency regressions exceeding **20% above previous baseline** must be reviewed before merge. Regressions must be documented with root cause analysis in the `docs/DECISIONS.md` ADR section for that sprint.

### Step 5: Archive Results

The JSON benchmark output should be committed to the `docs/benchmark-history/` directory with the naming convention:
```
docs/benchmark-history/benchmark_{YYYY-MM-DD}_sprint{N}.json
```

---

## 8. CI/CD Integration

The benchmark is integrated into the GitHub Actions pipeline as an optional post-test step. In CI environments, the benchmark runs against an in-memory SQLite database and is used for smoke testing only. Full MySQL benchmark testing is performed in staging environments against production-equivalent data volumes.

```yaml
# .github/workflows/ci.yml (benchmark stage)
- name: Run Benchmark Smoke Test
  run: php artisan suntrack:benchmark-run --json
```
