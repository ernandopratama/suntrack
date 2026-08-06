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
    public function getFilteredPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with('pic')
            ->whereHas('brand', fn($q) => $q->where('company_id', $companyId));

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
