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
    public function getFilteredPaginated(?string $companyId = null, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()
            ->when($companyId !== null, fn($q) => $q->where('company_id', $companyId));

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
