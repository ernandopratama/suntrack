<?php

namespace App\Services\Search\Drivers;

use App\Contracts\Search\SearchDriverInterface;
use Illuminate\Support\Facades\Log;

/**
 * Meilisearch search driver stub (ADR-028).
 * Implement using meilisearch/meilisearch-php or laravel/scout when Meilisearch is provisioned.
 */
class MeilisearchSearchDriver implements SearchDriverInterface
{
    public function isAvailable(): bool
    {
        // Returns true when MEILISEARCH_HOST is configured
        return filled(config('services.meilisearch.host'));
    }

    public function driverName(): string
    {
        return 'meilisearch';
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function search(string $query, array $types, int $limit, int|string|null $companyId): array
    {
        // TODO: Implement using Laravel Scout + Meilisearch when provisioned.
        // Reference: https://laravel.com/docs/scout#meilisearch
        Log::info("MeilisearchSearchDriver: search called but not yet implemented. Query: [{$query}]");

        return [];
    }
}
