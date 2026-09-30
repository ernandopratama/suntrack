<?php

namespace App\Repositories;

use App\Models\BusinessProspect;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class BusinessProspectRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return BusinessProspect::class;
    }

    /** @param array<string, mixed> $filters */
    public function getFilteredPaginated(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $trashed = $filters['trashed'] ?? null;
        $query = ($trashed === 'only' ? BusinessProspect::onlyTrashed() : BusinessProspect::query())->with([
            'marketplaceLinks',
            'pic:id,name',
            'creator:id,name',
            'convertedCompany:id,name',
            'convertedBrand:id,name',
        ]);

        $query = $this->scopeForUser($query, $user);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('marketplaceLinks', fn (Builder $links) => $links->where('url', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['conversion_status'])) {
            $query->where('conversion_status', $filters['conversion_status']);
        }

        if (! empty($filters['pic_id'])) {
            $query->where('pic_id', $filters['pic_id']);
        }

        if (! empty($filters['analysis_date'])) {
            $query->whereDate('analyzed_at', $filters['analysis_date']);
        } elseif (! empty($filters['analysis_month'])) {
            [$year, $month] = array_pad(explode('-', (string) $filters['analysis_month'], 2), 2, null);
            if (is_numeric($year) && is_numeric($month)) {
                $query->whereYear('analyzed_at', (int) $year)
                    ->whereMonth('analyzed_at', (int) $month);
            }
        } elseif (! empty($filters['analysis_year']) && is_numeric($filters['analysis_year'])) {
            $query->whereYear('analyzed_at', (int) $filters['analysis_year']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
