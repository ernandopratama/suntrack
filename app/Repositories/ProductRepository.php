<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;

class ProductRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * Get paginated products filtered by company and optional search/status/brand_id criteria.
     */
    public function getFilteredPaginated(User|string|null $scope = null, array $filters = [], int $perPage = 15)
    {
        $query = $this->newQuery()
            ->with('brand')
            ->withCount('variants');

        if ($scope instanceof User) {
            $query = $this->scopeForUser($query, $scope);
        } elseif ($scope !== null) {
            $query->whereHas('brand', fn ($brand) => $brand->where('company_id', $scope));
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%"));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find product by ID with eager loaded brand and variants.
     */
    public function findWithVariants(string|int $id)
    {
        return $this->newQuery()->with(['brand', 'variants'])->findOrFail($id);
    }
}
