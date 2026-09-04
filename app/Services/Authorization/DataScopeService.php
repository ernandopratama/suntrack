<?php

namespace App\Services\Authorization;

use App\Models\ActivityLog;
use App\Models\ApprovalHistory;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SecureLink;
use App\Models\Task;
use App\Models\User;
use App\Models\Variant;
use App\Support\Rbac\RbacRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DataScopeService
{
    private const SCOPE_CACHE_SECONDS = 300;

    public function hasGlobalScope(User $user): bool
    {
        return $user->hasAnyRole([
            RbacRegistry::SUPER_ADMIN,
            RbacRegistry::ADMIN,
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query, User $user): Builder
    {
        match ($query->getModel()::class) {
            Company::class => $this->scopeCompanies($query, $user),
            Brand::class => $this->scopeBrands($query, $user),
            Campaign::class => $this->scopeCampaigns($query, $user),
            Promotion::class => $this->scopePromotions($query, $user),
            Task::class => $this->scopeTasks($query, $user),
            Product::class => $this->scopeProducts($query, $user),
            Variant::class => $this->scopeVariants($query, $user),
            ActivityLog::class => $this->scopeActivityLogs($query, $user),
            ApprovalHistory::class => $this->scopeApprovalHistories($query, $user),
            SecureLink::class => $this->scopeSecureLinks($query, $user),
            Comment::class => $this->scopeComments($query, $user),
            default => $query->whereRaw('1 = 0'),
        };

        return $query;
    }

    public function scopeCompanies(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user) {
            $scope->whereExists(function ($assignment) use ($user) {
                $assignment->selectRaw('1')
                    ->from('user_company_assignments')
                    ->whereColumn('user_company_assignments.company_id', 'companies.id')
                    ->where('user_company_assignments.user_id', $user->id);
            })->orWhereExists(function ($assignment) use ($user) {
                $assignment->selectRaw('1')
                    ->from('user_brand_assignments')
                    ->join('brands', 'brands.id', '=', 'user_brand_assignments.brand_id')
                    ->whereColumn('brands.company_id', 'companies.id')
                    ->whereNull('brands.deleted_at')
                    ->where('user_brand_assignments.user_id', $user->id);
            });
        });
    }

    public function scopeBrands(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user) {
            $scope->whereExists(function ($assignment) use ($user) {
                $assignment->selectRaw('1')
                    ->from('user_brand_assignments')
                    ->whereColumn('user_brand_assignments.brand_id', 'brands.id')
                    ->where('user_brand_assignments.user_id', $user->id);
            })->orWhereExists(function ($assignment) use ($user) {
                $assignment->selectRaw('1')
                    ->from('user_company_assignments')
                    ->whereColumn('user_company_assignments.company_id', 'brands.company_id')
                    ->where('user_company_assignments.user_id', $user->id);
            });
        });
    }

    public function scopeCampaigns(Builder $query, User $user): Builder
    {
        return $this->scopeThroughBrand($query, $user, 'brand');
    }

    public function scopePromotions(Builder $query, User $user): Builder
    {
        return $this->scopeThroughBrand($query, $user, 'brand');
    }

    public function scopeTasks(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->whereHas('campaign.brand', fn (Builder $brand) => $this->scopeBrands($brand, $user));
    }

    public function scopeProducts(Builder $query, User $user): Builder
    {
        return $this->scopeThroughBrand($query, $user, 'brand');
    }

    public function scopeVariants(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->whereHas('product.brand', fn (Builder $brand) => $this->scopeBrands($brand, $user));
    }

    public function scopeActivityLogs(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->where(function (Builder $logs) use ($user) {
            $this->addMorphScope($logs, Company::class, $this->scopeCompanies(Company::query(), $user));
            $this->addMorphScope($logs, Brand::class, $this->scopeBrands(Brand::query(), $user), true);
            $this->addMorphScope($logs, Campaign::class, $this->scopeCampaigns(Campaign::query(), $user), true);
            $this->addMorphScope($logs, Promotion::class, $this->scopePromotions(Promotion::query(), $user), true);
            $this->addMorphScope($logs, Task::class, $this->scopeTasks(Task::query(), $user), true);
            $this->addMorphScope($logs, Product::class, $this->scopeProducts(Product::query(), $user), true);
            $this->addMorphScope($logs, Variant::class, $this->scopeVariants(Variant::query(), $user), true);
        });
    }

    public function scopeApprovalHistories(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->whereHas('promotion', fn (Builder $promotion) => $this->scopePromotions($promotion, $user));
    }

    public function scopeSecureLinks(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->where(function (Builder $links) use ($user) {
            $links->where(function (Builder $campaignLinks) use ($user) {
                $campaignLinks->where('linkable_type', Campaign::class)
                    ->whereIn('linkable_id', $this->scopeCampaigns(Campaign::query(), $user)->select('id'));
            })->orWhere(function (Builder $promotionLinks) use ($user) {
                $promotionLinks->where('linkable_type', Promotion::class)
                    ->whereIn('linkable_id', $this->scopePromotions(Promotion::query(), $user)->select('id'));
            });
        });
    }

    public function scopeComments(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->where(function (Builder $comments) use ($user) {
            $comments->where(function (Builder $campaignComments) use ($user) {
                $campaignComments->where('commentable_type', Campaign::class)
                    ->whereIn('commentable_id', $this->scopeCampaigns(Campaign::query(), $user)->select('id'));
            })->orWhere(function (Builder $promotionComments) use ($user) {
                $promotionComments->where('commentable_type', Promotion::class)
                    ->whereIn('commentable_id', $this->scopePromotions(Promotion::query(), $user)->select('id'));
            });
        });
    }

    public function canAccess(User $user, Model $model): bool
    {
        if ($this->hasGlobalScope($user)) {
            return true;
        }

        return $this->scope($model->newQuery()->whereKey($model->getKey()), $user)->exists();
    }

    public function canAccessBrandId(User $user, string $brandId): bool
    {
        return $this->scopeBrands(Brand::query()->whereKey($brandId), $user)->exists();
    }

    public function canAccessCampaignId(User $user, string $campaignId): bool
    {
        return $this->scopeCampaigns(Campaign::query()->whereKey($campaignId), $user)->exists();
    }

    /** @return Collection<int, string> */
    public function effectiveCompanyIds(User $user): Collection
    {
        return collect(Cache::remember(
            $this->scopeCacheKey($user, 'companies'),
            self::SCOPE_CACHE_SECONDS,
            fn () => $this->scopeCompanies(Company::query(), $user)
                ->pluck('companies.id')
                ->values()
                ->all()
        ));
    }

    /** @return Collection<int, string> */
    public function effectiveBrandIds(User $user): Collection
    {
        return collect(Cache::remember(
            $this->scopeCacheKey($user, 'brands'),
            self::SCOPE_CACHE_SECONDS,
            fn () => $this->scopeBrands(Brand::query(), $user)
                ->pluck('brands.id')
                ->values()
                ->all()
        ));
    }

    public function forgetCachedScope(User $user): void
    {
        Cache::forget($this->scopeCacheKey($user, 'companies'));
        Cache::forget($this->scopeCacheKey($user, 'brands'));
    }

    private function scopeThroughBrand(Builder $query, User $user, string $relation): Builder
    {
        if ($this->hasGlobalScope($user)) {
            return $query;
        }

        return $query->whereHas($relation, fn (Builder $brand) => $this->scopeBrands($brand, $user));
    }

    private function addMorphScope(Builder $logs, string $modelClass, Builder $scopedModels, bool $or = false): void
    {
        $method = $or ? 'orWhere' : 'where';

        $logs->{$method}(function (Builder $type) use ($modelClass, $scopedModels) {
            $type->where('loggable_type', $modelClass)
                ->whereIn('loggable_id', $scopedModels->select('id'));
        });
    }

    private function scopeCacheKey(User $user, string $scope): string
    {
        return "rbac.scope.{$user->id}.{$scope}";
    }
}
