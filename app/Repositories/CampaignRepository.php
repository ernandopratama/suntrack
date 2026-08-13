<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Campaign::class;
    }

    /**
     * Get paginated campaigns filtered by company and optional search/status criteria.
     * Prevents N+1 by eager loading 'pic' relationship.
     *
     * @param array<string, mixed> $filters
     */
    public function getFilteredPaginated(?string $companyId = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with('pic')
            ->when($companyId !== null, fn($q) => $q->whereHas('brand', fn($b) => $b->where('company_id', $companyId)));

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
