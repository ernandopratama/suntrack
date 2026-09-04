<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    public function getFilteredPaginated(?User $actor = null, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()->with('roles');

        if ($actor !== null && ! $actor->hasAnyRole(['Super Admin', 'Admin'])) {
            $query->whereRaw('1 = 0');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
