<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\User;
use App\Services\Workflow\CampaignDeadlineService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignRepository extends BaseRepository
{
    public function __construct(private CampaignDeadlineService $deadlines)
    {
        parent::__construct();
    }

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
        $query = $this->newQuery()->with(['brand', 'pic', 'creator', 'members']);

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

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $query = $this->deadlines->applyMonitoringFilter($query, $filters['monitoring'] ?? null);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }
}
