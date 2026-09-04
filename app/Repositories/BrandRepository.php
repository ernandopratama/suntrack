<?php

namespace App\Repositories;

use App\Models\Brand;
use App\Models\User;

class BrandRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Brand::class;
    }

    /**
     * Get paginated brands filtered by company.
     */
    public function getFilteredPaginated(User|string|null $scope = null, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery();

        if ($scope instanceof User) {
            $query = $this->scopeForUser($query, $scope);
        } elseif ($scope !== null) {
            $query->where('company_id', $scope);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
