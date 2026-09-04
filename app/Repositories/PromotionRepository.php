<?php

namespace App\Repositories;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class PromotionRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Promotion::class;
    }

    /**
     * Get paginated promotions filtered by company and optional search/status/campaign criteria.
     * Prevents N+1 by eager loading campaign and brand, and aggregating variant counts.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredPaginated(User|string|null $scope = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(['campaign', 'brand'])
            ->withCount('variants');

        if ($scope instanceof User) {
            $query = $this->scopeForUser($query, $scope);
        } elseif ($scope !== null) {
            $query->whereHas('brand', fn ($brand) => $brand->where('company_id', $scope));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find a promotion by ID with all relationships needed for full details and workspace.
     */
    public function findWithDetails(string|int $id): Model
    {
        return $this->newQuery()
            ->with(['campaign', 'brand', 'variants', 'comments', 'approvalHistories', 'secureLinks'])
            ->findOrFail($id);
    }
}
