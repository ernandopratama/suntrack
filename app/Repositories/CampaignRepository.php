<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\User;
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
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredPaginated(User|string|null $scope = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with('pic');

        if ($scope instanceof User) {
            $query = $this->scopeForUser($query, $scope);
        } elseif ($scope !== null) {
            $query->whereHas('brand', fn ($brand) => $brand->where('company_id', $scope));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
