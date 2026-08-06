<?php

namespace App\Contracts\Search;

interface SearchDriverInterface
{
    /**
     * Execute a search query across one or more entity types.
     *
     * @param  string  $query      Search term
     * @param  array<string>  $types  Entity types to search (e.g. ['campaign','product'])
     * @param  int     $limit      Max results per entity type
     * @param  int|string  $companyId  Tenant isolation boundary
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function search(string $query, array $types, int $limit, int|string $companyId): array;

    /**
     * Check whether this driver is available/configured in the current environment.
     */
    public function isAvailable(): bool;

    /**
     * Return the human-readable driver name for debugging/logging.
     */
    public function driverName(): string;
}
