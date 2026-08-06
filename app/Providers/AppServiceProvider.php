<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        \App\Models\Campaign::observe(\App\Observers\CampaignObserver::class);
        \App\Models\Promotion::observe(\App\Observers\PromotionObserver::class);
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\Variant::observe(\App\Observers\VariantObserver::class);
        \App\Models\SystemSetting::observe(\App\Observers\SystemSettingObserver::class);
    }
}
