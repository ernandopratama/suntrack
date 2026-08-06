<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    public function getFilteredPaginated(int $companyId, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()
            ->where('company_id', $companyId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
