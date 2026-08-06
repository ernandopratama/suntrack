<?php

namespace App\Services\Search\Drivers;

use App\Contracts\Search\SearchDriverInterface;
use Illuminate\Support\Facades\Log;

/**
 * Elasticsearch / OpenSearch driver stub (ADR-028).
 * Implement using elastic/elasticsearch-php when Elasticsearch is provisioned.
 */
class ElasticsearchSearchDriver implements SearchDriverInterface
{
    public function isAvailable(): bool
    {
        // Returns true when ELASTICSEARCH_HOST is configured
        return filled(env('ELASTICSEARCH_HOST'));
    }

    public function driverName(): string
    {
        return 'elasticsearch';
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function search(string $query, array $types, int $limit, int|string $companyId): array
    {
        // TODO: Implement using elastic/elasticsearch-php when provisioned.
        // Reference: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current
        Log::info("ElasticsearchSearchDriver: search called but not yet implemented. Query: [{$query}]");
        return [];
    }
}
