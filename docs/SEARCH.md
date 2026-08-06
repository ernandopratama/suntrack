# Global Search Engine — SunTrack

> **Sprint 11 · ADR-028: Global Search Engine with Driver Pattern**

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Supported Entity Types](#supported-entity-types)
4. [API Reference](#api-reference)
5. [Request & Response Example](#request--response-example)
6. [Driver Resolution Order](#driver-resolution-order)
7. [Adding a New Driver](#adding-a-new-driver)
8. [Caching](#caching)
9. [Rate Limiting](#rate-limiting)
10. [Environment Configuration](#environment-configuration)
11. [SLA & Constraints](#sla--constraints)

---

## Overview

The **Global Search Engine** provides a unified, driver-based search interface across all major SunTrack entity types. Introduced in Sprint 11, this system decouples the search mechanism from any single search backend through the **Driver Pattern** (see ADR-028), enabling seamless swapping between Database, Meilisearch, and Elasticsearch backends without modifying application code.

Key design goals:
- **Backend-agnostic**: consumers call one API regardless of the underlying search technology.
- **Progressive enhancement**: falls back gracefully to database search if no external engine is configured.
- **Horizontally scalable**: Meilisearch and Elasticsearch drivers unlock sub-millisecond full-text search at scale.

---

## Architecture

### SearchDriverInterface Contract

All search drivers must implement the following interface:

```php
// app/Contracts/Search/SearchDriverInterface.php

namespace App\Contracts\Search;

interface SearchDriverInterface
{
    /**
     * Execute a search query.
     *
     * @param  string  $query       Raw search string (min. 2 chars).
     * @param  array   $types       Entity types to search (e.g. ['campaign', 'product']).
     * @param  int     $companyId   Scope results to this company.
     * @param  int     $limit       Maximum results per type.
     * @return array                Keyed by entity type.
     */
    public function search(string $query, array $types, int $companyId, int $limit): array;

    /**
     * Determine whether this driver is available and configured.
     */
    public function isAvailable(): bool;
}
```

### Available Drivers

| Driver | Class | Backend | Trigger |
|---|---|---|---|
| **Meilisearch** | `MeilisearchSearchDriver` | Meilisearch v1.x | `MEILISEARCH_HOST` env var set |
| **Elasticsearch** | `ElasticsearchSearchDriver` | Elasticsearch 8.x | `ELASTICSEARCH_HOST` env var set |
| **Database** | `DatabaseSearchDriver` | MySQL / PostgreSQL via Eloquent | Always available (fallback) |

### GlobalSearchService

`GlobalSearchService` is the central resolution component that selects the appropriate driver at runtime:

```php
// app/Services/Search/GlobalSearchService.php

namespace App\Services\Search;

use App\Contracts\Search\SearchDriverInterface;

class GlobalSearchService
{
    /** @param SearchDriverInterface[] $drivers Ordered by priority. */
    public function __construct(private array $drivers) {}

    public function search(string $query, array $types, int $companyId, int $limit = 10): array
    {
        foreach ($this->drivers as $driver) {
            if ($driver->isAvailable()) {
                return $driver->search($query, $types, $companyId, $limit);
            }
        }

        throw new \RuntimeException('No search driver available.');
    }
}
```

The `$drivers` array is injected via the `SearchServiceProvider`, ordered by priority (Meilisearch → Elasticsearch → Database).

---

## Supported Entity Types

| Type | Model | Searchable Fields |
|---|---|---|
| `campaign` | `Campaign` | `name`, `description`, `code` |
| `promotion` | `Promotion` | `name`, `notes`, `external_ref` |
| `product` | `Product` | `name`, `sku`, `barcode` |
| `variant` | `Variant` | `name`, `sku`, `attributes` |
| `activity_log` | `ActivityLog` | `description`, `subject_type`, `causer_name` |
| `comment` | `Comment` | `body`, `author_name` |

> **Note:** Passing an unrecognized type is silently ignored to maintain API stability.

---

## API Reference

### Global Search Endpoint

```
GET /api/v1/admin/search
```

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `q` | `string` | ✅ | — | Search query string (min. 2 characters) |
| `types[]` | `string[]` | ❌ | all types | Entity types to include in results |
| `limit` | `integer` | ❌ | `10` | Max results per type (max `50`) |

#### Headers

| Header | Value |
|---|---|
| `Authorization` | `Bearer {token}` |
| `Accept` | `application/json` |
| `X-Company-ID` | `{companyId}` |

---

## Request & Response Example

### Request

```http
GET /api/v1/admin/search?q=summer&types[]=campaign&types[]=product&limit=5
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
Accept: application/json
X-Company-ID: 42
```

### Response — `200 OK`

```json
{
  "data": {
    "campaign": [
      {
        "id": 101,
        "type": "campaign",
        "label": "Summer Flash Sale 2025",
        "subtitle": "Active · 12 promotions",
        "url": "/admin/campaigns/101"
      }
    ],
    "product": [
      {
        "id": 88,
        "type": "product",
        "label": "Summer Breeze Moisturizer",
        "subtitle": "SKU: SBM-001",
        "url": "/admin/products/88"
      },
      {
        "id": 94,
        "type": "product",
        "label": "Summer Glow Serum",
        "subtitle": "SKU: SGS-204",
        "url": "/admin/products/94"
      }
    ]
  },
  "meta": {
    "query": "summer",
    "driver": "meilisearch",
    "took_ms": 4,
    "cached": false
  }
}
```

### Response — `422 Unprocessable Entity` (query too short)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "q": ["The q field must be at least 2 characters."]
  }
}
```

### Response — `429 Too Many Requests`

```json
{
  "message": "Too many search requests. Please wait before trying again.",
  "retry_after": 28
}
```

---

## Driver Resolution Order

The `GlobalSearchService` iterates drivers in the following priority order and selects the **first available** driver:

```
1. MeilisearchSearchDriver   →  isAvailable() checks MEILISEARCH_HOST + reachability
2. ElasticsearchSearchDriver →  isAvailable() checks ELASTICSEARCH_HOST + reachability
3. DatabaseSearchDriver      →  always returns true (guaranteed fallback)
```

```
┌─────────────────────────────────────────────────────────────┐
│                    Search Request                           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │  Meilisearch Driver │ ── available? ──► YES → Execute
              └────────────────────┘                         │
                         │ NO                                │
                         ▼                                   │
           ┌───────────────────────────┐                     │
           │  Elasticsearch Driver     │ ── available? ──► YES → Execute
           └───────────────────────────┘                     │
                         │ NO                                │
                         ▼                                   │
              ┌─────────────────────┐                        │
              │  Database Driver    │ ── always YES ─────────►
              └─────────────────────┘
```

---

## Adding a New Driver

To integrate a new search backend (e.g., Typesense), follow these steps:

### Step 1 — Implement the Interface

```php
// app/Services/Search/Drivers/TypesenseSearchDriver.php

namespace App\Services\Search\Drivers;

use App\Contracts\Search\SearchDriverInterface;

class TypesenseSearchDriver implements SearchDriverInterface
{
    public function __construct(private TypesenseClient $client) {}

    public function isAvailable(): bool
    {
        return config('search.typesense.host') !== null
            && $this->client->ping();
    }

    public function search(string $query, array $types, int $companyId, int $limit): array
    {
        // implement Typesense multi-search here
        return [];
    }
}
```

### Step 2 — Register in the Service Provider

```php
// app/Providers/SearchServiceProvider.php

$drivers = [
    app(TypesenseSearchDriver::class),   // highest priority
    app(MeilisearchSearchDriver::class),
    app(ElasticsearchSearchDriver::class),
    app(DatabaseSearchDriver::class),    // always last
];

$this->app->singleton(GlobalSearchService::class, fn () =>
    new GlobalSearchService($drivers)
);
```

### Step 3 — Add Configuration

```php
// config/search.php

'typesense' => [
    'host'    => env('TYPESENSE_HOST'),
    'api_key' => env('TYPESENSE_API_KEY'),
],
```

No changes to controllers, routes, or tests are required.

---

## Caching

Search results are cached using **Redis** with the following strategy:

| Property | Value |
|---|---|
| Cache TTL | **60 seconds** |
| Cache Key | `search:{companyId}:{md5(query)}:{md5(types_sorted)}` |
| Cache Store | `redis` (via Laravel Cache facade) |
| Cache Tags | `['search', 'company:{companyId}']` |
| Invalidation | On entity create / update / delete via Observer |

```php
$cacheKey = sprintf(
    'search:%d:%s:%s',
    $companyId,
    md5($query),
    md5(implode(',', $sortedTypes))
);

return Cache::tags(['search', "company:{$companyId}"])
    ->remember($cacheKey, 60, fn () => $driver->search(...));
```

> **Tip:** To manually flush search cache for a company, run:
> ```bash
> php artisan cache:tags:flush search company:42
> ```

---

## Rate Limiting

The search endpoint is protected by a dedicated rate limiter defined in `RouteServiceProvider`:

| Property | Value |
|---|---|
| Max requests | **30 per minute** |
| Scope | Per authenticated user (`user_id`) |
| Response on limit | `429 Too Many Requests` with `Retry-After` header |

```php
// app/Providers/RouteServiceProvider.php

RateLimiter::for('search', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
```

---

## Environment Configuration

### Activating Meilisearch

Add the following to your `.env` file:

```dotenv
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=your_master_key_here
```

Then index existing data:

```bash
docker compose exec app php artisan scout:import "App\Models\Campaign"
docker compose exec app php artisan scout:import "App\Models\Product"
```

### Activating Elasticsearch

Add the following to your `.env` file:

```dotenv
ELASTICSEARCH_HOST=http://elasticsearch:9200
ELASTICSEARCH_USERNAME=elastic
ELASTICSEARCH_PASSWORD=your_password_here
```

Then create the index mappings:

```bash
docker compose exec app php artisan elasticsearch:index:create
docker compose exec app php artisan elasticsearch:index:populate
```

### Database Fallback (Default)

No additional configuration is required. The `DatabaseSearchDriver` is always active and uses Laravel's query builder with `LIKE` clauses:

```dotenv
# No search-specific env vars → Database driver is used automatically
```

---

## SLA & Constraints

| Constraint | Specification |
|---|---|
| Minimum query length | **2 characters** — shorter queries are rejected with `422` |
| Maximum query length | 100 characters |
| Maximum results per type | 50 |
| Default results per type | 10 |
| Rate limit | 30 requests / minute / user |
| Cache TTL | 60 seconds |
| Expected P95 latency (Meilisearch) | < 20 ms |
| Expected P95 latency (Database) | < 200 ms |
| Driver resolution timeout | 2 seconds before fallback to next driver |

---

*Last updated: Sprint 11 · SunTrack Platform*
