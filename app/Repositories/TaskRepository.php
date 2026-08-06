<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Task::class;
    }

    public function getFilteredPaginated(?string $campaignId = null, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()->with('campaign.brand');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (!empty($filters['status'])) {
            $query->where('progress_status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
