<?php

namespace App\Services\Search;

use App\Contracts\Search\SearchDriverInterface;
use App\Services\Cache\CacheService;
use App\Services\Search\Drivers\DatabaseSearchDriver;
use App\Services\Search\Drivers\ElasticsearchSearchDriver;
use App\Services\Search\Drivers\MeilisearchSearchDriver;
use Illuminate\Support\Facades\Log;

/**
 * Global Search Service — Driver Pattern (ADR-028).
 * Resolves the appropriate search driver at runtime based on environment configuration.
 * Driver resolution order: Meilisearch → Elasticsearch → Database (fallback).
 */
class GlobalSearchService
{
    protected SearchDriverInterface $driver;

    public function __construct(
        protected CacheService $cache = new CacheService
    ) {
        $this->driver = $this->resolveDriver();
    }

    /**
     * Execute a global search query, with results cached per user+query for 60 seconds.
     *
     * @param  array<string>  $types
     * @param  int|string|null  $companyId  Optional Company scope; null means global access
     * @return array<string, mixed>
     */
    public function search(string $query, array $types = [], int $limit = 5, int|string|null $companyId = null): array
    {
        if (strlen(trim($query)) < 2) {
            return ['results' => [], 'driver' => $this->driver->driverName(), 'query' => $query];
        }

        $scopeKey = $companyId ?? 'global';
        $cacheKey = 'global_search_'.md5($query.implode(',', $types).$scopeKey.$limit);
        $results = $this->cache->remember(['search'], $cacheKey, 60, function () use ($query, $types, $limit, $companyId) {
            return $this->driver->search($query, $types, $limit, $companyId);
        });

        return [
            'results' => $results,
            'driver' => $this->driver->driverName(),
            'query' => $query,
            'total' => array_sum(array_map('count', $results)),
        ];
    }

    /**
     * Resolve the appropriate search driver at runtime.
     */
    protected function resolveDriver(): SearchDriverInterface
    {
        $drivers = [
            new MeilisearchSearchDriver,
            new ElasticsearchSearchDriver,
        ];

        foreach ($drivers as $driver) {
            if ($driver->isAvailable()) {
                Log::info("GlobalSearchService: using driver [{$driver->driverName()}]");

                return $driver;
            }
        }

        return new DatabaseSearchDriver;
    }

    /**
     * Return the name of the currently active driver.
     */
    public function activeDriver(): string
    {
        return $this->driver->driverName();
    }
}
