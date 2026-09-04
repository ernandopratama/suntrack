<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;

class CompanyRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Company::class;
    }

    public function getFilteredPaginated(?User $user = null, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()
            ->with('brands')
            ->withCount(['brands', 'assignedUsers as users_count']);

        if ($user !== null) {
            $query = $this->scopeForUser($query, $user);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
