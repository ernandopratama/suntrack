<?php

namespace App\Repositories;

use App\Models\Company;

class CompanyRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Company::class;
    }

    public function getFilteredPaginated(array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()->with('brands');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
