<?php

namespace App\Repositories;

use App\Models\Brand;

class BrandRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Brand::class;
    }

    /**
     * Get paginated brands filtered by company.
     */
    public function getFilteredPaginated(int $companyId, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()
            ->where('company_id', $companyId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
