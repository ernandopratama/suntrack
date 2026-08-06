<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

// ==========================================
// V1 API ROUTES
// ==========================================

Route::prefix('v1')->group(function () {

    // ------------------------------------------
    // Auth Routes (SPA / Sanctum)
    // ------------------------------------------
    Route::middleware(['web'])->prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
        });
    });

    // ------------------------------------------
    // Admin Routes
    // ------------------------------------------
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        // Operational Command Center & Reporting Foundation
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\V1\DashboardController::class, 'stats']);
        Route::get('/dashboard/export', [\App\Http\Controllers\Api\V1\DashboardController::class, 'exportReport']);

        Route::apiResource('campaigns', \App\Http\Controllers\Api\V1\CampaignController::class);

        Route::get('/companies', [\App\Http\Controllers\Api\V1\CompanyController::class, 'index']);

        Route::apiResource('companies', \App\Http\Controllers\Api\V1\CompanyController::class);

        Route::apiResource('brands', \App\Http\Controllers\Api\V1\BrandController::class);

        Route::apiResource('tasks', \App\Http\Controllers\Api\V1\TaskController::class);

        Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class);

        Route::apiResource('promotions', \App\Http\Controllers\Api\V1\PromotionController::class);

        // Product & Variant (nested)
        Route::post('products/import', [\App\Http\Controllers\Api\V1\ProductController::class, 'import']);
        Route::post('products/bulk-delete', [\App\Http\Controllers\Api\V1\ProductController::class, 'bulkDestroy']);
        Route::apiResource('products', \App\Http\Controllers\Api\V1\ProductController::class);
        Route::apiResource('products.variants', \App\Http\Controllers\Api\V1\VariantController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Promotion Variant Mapping & Pricing
        Route::prefix('promotions/{promotion}/variants')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PromotionVariantController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\PromotionVariantController::class, 'store']);
            Route::delete('/{variant}', [\App\Http\Controllers\Api\V1\PromotionVariantController::class, 'destroy']);
        });

        // Secure Links & Discussions Management (Promotions)
        Route::post('promotions/{promotion}/batch-approval', [\App\Http\Controllers\Api\V1\PromotionController::class, 'batchApproval']);
        Route::prefix('promotions/{promotion}/secure-link')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'showPromotionLink']);
            Route::post('/', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'storePromotionLink']);
            Route::put('/regenerate', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'regeneratePromotionLink']);
            Route::delete('/', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'destroyPromotionLink']);
        });
        Route::get('promotions/{promotion}/approval-histories', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'getPromotionHistories']);
        Route::post('promotions/{promotion}/comments', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'storePromotionComment']);
        Route::post('campaigns/{campaign}/comments', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'storeCampaignComment']);

        // Secure Links & Discussions Management (Campaigns)
        Route::prefix('campaigns/{campaign}/secure-link')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'showCampaignLink']);
            Route::post('/', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'storeCampaignLink']);
            Route::delete('/', [\App\Http\Controllers\Api\V1\SecureLinkController::class, 'destroyCampaignLink']);
        });
        // System Settings Management (Sprint 9)
        Route::get('/settings', [\App\Http\Controllers\Api\V1\SystemSettingController::class, 'index'])->middleware('permission:settings.view');
        Route::put('/settings', [\App\Http\Controllers\Api\V1\SystemSettingController::class, 'update'])->middleware('permission:settings.update');

        // ----------------------------------------
        // Sprint 11: Global Search Engine (ADR-028)
        // ----------------------------------------
        Route::get('/search', \App\Http\Controllers\Api\V1\SearchController::class);

        // ----------------------------------------
        // Sprint 11: Enterprise Audit Dashboard
        // ----------------------------------------
        Route::prefix('audit')->group(function () {
            Route::get('/login-history',    [\App\Http\Controllers\Api\V1\AuditController::class, 'loginHistory']);
            Route::get('/queue-history',    [\App\Http\Controllers\Api\V1\AuditController::class, 'queueHistory']);
            Route::get('/error-logs',       [\App\Http\Controllers\Api\V1\AuditController::class, 'errorLogs']);
            Route::get('/summary',          [\App\Http\Controllers\Api\V1\AuditController::class, 'summary']);
        });

        // ----------------------------------------
        // Sprint 11: Notification Center (ADR-029)
        // ----------------------------------------
        Route::prefix('notifications')->group(function () {
            Route::get('/',                         [\App\Http\Controllers\Api\V1\NotificationCenterController::class, 'index']);
            Route::get('/summary',                  [\App\Http\Controllers\Api\V1\NotificationCenterController::class, 'summary']);
            Route::get('/{notification}',           [\App\Http\Controllers\Api\V1\NotificationCenterController::class, 'show']);
            Route::post('/{notification}/retry',    [\App\Http\Controllers\Api\V1\NotificationCenterController::class, 'retry']);
            Route::post('/{notification}/cancel',   [\App\Http\Controllers\Api\V1\NotificationCenterController::class, 'cancel']);
        });

        // ----------------------------------------
        // Sprint 11: System Monitoring Dashboard (7 categories)
        // ----------------------------------------
        Route::prefix('system')->group(function () {
            Route::get('/health',           [\App\Http\Controllers\Api\V1\SystemMonitorController::class, 'health']);
            Route::get('/queue-stats',      [\App\Http\Controllers\Api\V1\SystemMonitorController::class, 'queueStats']);
            Route::get('/cache-stats',      [\App\Http\Controllers\Api\V1\SystemMonitorController::class, 'cacheStats']);
            Route::get('/storage-stats',    [\App\Http\Controllers\Api\V1\SystemMonitorController::class, 'storageStats']);
            Route::get('/db-stats',         [\App\Http\Controllers\Api\V1\SystemMonitorController::class, 'dbStats']);
            Route::get('/metrics',          [\App\Http\Controllers\Api\V1\SystemMonitorController::class, 'prometheusMetrics']);
        });

        // ----------------------------------------
        // Sprint 11: Pricing Analytics & Margin Simulation
        // ----------------------------------------
        Route::prefix('analytics/pricing')->group(function () {
            Route::get('/overview',          [\App\Http\Controllers\Api\V1\PricingAnalyticsController::class, 'overview']);
            Route::get('/margin-violations', [\App\Http\Controllers\Api\V1\PricingAnalyticsController::class, 'marginViolations']);
            Route::post('/simulate',         [\App\Http\Controllers\Api\V1\PricingAnalyticsController::class, 'simulate']);
        });

        // ----------------------------------------
        // Sprint 11: Saved Filters & User Preferences
        // ----------------------------------------
        Route::prefix('saved-filters')->group(function () {
            Route::get('/',                         [\App\Http\Controllers\Api\V1\SavedFilterController::class, 'index']);
            Route::post('/',                        [\App\Http\Controllers\Api\V1\SavedFilterController::class, 'store']);
            Route::delete('/{id}',                  [\App\Http\Controllers\Api\V1\SavedFilterController::class, 'destroy']);
            Route::patch('/{id}/set-default',       [\App\Http\Controllers\Api\V1\SavedFilterController::class, 'setDefault']);
        });
        Route::get('/me/preferences',   [\App\Http\Controllers\Api\V1\UserPreferenceController::class, 'show']);
        Route::put('/me/preferences',   [\App\Http\Controllers\Api\V1\UserPreferenceController::class, 'update']);

        // ----------------------------------------
        // Sprint 11: Business Intelligence Reports
        // ----------------------------------------
        Route::prefix('reports')->group(function () {
            Route::get('/approval-performance',         [\App\Http\Controllers\Api\V1\ReportController::class, 'approvalPerformance']);
            Route::get('/promotion-effectiveness',      [\App\Http\Controllers\Api\V1\ReportController::class, 'promotionEffectiveness']);
            Route::get('/brand-activity/{brandId}',     [\App\Http\Controllers\Api\V1\ReportController::class, 'brandActivity']);
        });
    });

    // ------------------------------------------
    // Public Routes (Secure Link & Monitoring)
    // ------------------------------------------
    Route::get('/health', [\App\Http\Controllers\Api\V1\HealthController::class, 'check']);
    Route::get('/public/settings', [\App\Http\Controllers\Api\V1\SystemSettingController::class, 'publicSettings']);

    Route::prefix('public/review/{token}')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'show']);
        Route::post('/identify', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'identify']);
        Route::post('/approval', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'approveVariant']);
        Route::post('/batch-approval', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'batchApproval']);
        Route::post('/comment', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'storeComment']);
        Route::post('/tasks/{task}/progress', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'updateTaskProgress']);
        Route::post('/tasks/{task}/visual', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'submitTaskVisual']);
        Route::delete('/tasks/{task}/visual', [\App\Http\Controllers\Api\V1\PublicReviewController::class, 'deleteTaskVisual']);
    });
});
