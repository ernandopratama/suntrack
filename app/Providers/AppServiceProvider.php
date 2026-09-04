<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\User;
use App\Models\Variant;
use App\Observers\CampaignObserver;
use App\Observers\ProductObserver;
use App\Observers\PromotionObserver;
use App\Observers\SystemSettingObserver;
use App\Observers\VariantObserver;
use App\Policies\BrandPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Policies\VariantPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Variant::class, VariantPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // Slow query logging for queries exceeding 100ms (ADR-021 Observability)
        DB::listen(function ($query) {
            if ($query->time > 100) {
                Log::warning("Slow query detected: {$query->sql} ({$query->time}ms)", [
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });

        // Register polymorphic actor types for ActivityLog actor relation.
        Relation::morphMap([
            'Admin' => User::class,
            'Brand' => Brand::class,
            'System' => User::class,
        ]);

        // Register automated Redis cache invalidation observers (Sprint 10)
        Campaign::observe(CampaignObserver::class);
        Promotion::observe(PromotionObserver::class);
        Product::observe(ProductObserver::class);
        Variant::observe(VariantObserver::class);
        SystemSetting::observe(SystemSettingObserver::class);
    }
}
