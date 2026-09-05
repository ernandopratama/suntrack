<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CollaborationController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationCenterController;
use App\Http\Controllers\Api\V1\PerformanceReportController;
use App\Http\Controllers\Api\V1\PricingAnalyticsController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\PromotionVariantController;
use App\Http\Controllers\Api\V1\PublicReviewController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SavedFilterController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SecureLinkController;
use App\Http\Controllers\Api\V1\SystemMonitorController;
use App\Http\Controllers\Api\V1\SystemSettingController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserPreferenceController;
use App\Http\Controllers\Api\V1\VariantController;
use App\Http\Controllers\Api\V1\WorkflowOptionController;
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
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
            ->middleware('permission:campaign.view');
        Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])
            ->middleware('permission:report.export');
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:activity.view');
        Route::get('/workflow/options', WorkflowOptionController::class);
        Route::get('/access/options', [UserController::class, 'accessOptions'])
            ->middleware('permission:access.view');
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}/users', [RoleController::class, 'users']);
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);

        Route::apiResource('campaigns', CampaignController::class)
            ->middlewareFor(['index', 'show'], 'permission:campaign.view')
            ->middlewareFor('store', 'permission:campaign.create')
            ->middlewareFor('update', 'permission:campaign.update')
            ->middlewareFor('destroy', 'permission:campaign.delete');
        Route::post('campaigns/{campaign}/transition', [CampaignController::class, 'transition'])
            ->middleware('permission:campaign.update');

        Route::apiResource('companies', CompanyController::class)
            ->middlewareFor(['index', 'show'], 'permission:company.view')
            ->middlewareFor('store', 'permission:company.create')
            ->middlewareFor('update', 'permission:company.update')
            ->middlewareFor('destroy', 'permission:company.delete');

        Route::apiResource('brands', BrandController::class)
            ->middlewareFor(['index', 'show'], 'permission:brand.view')
            ->middlewareFor('store', 'permission:brand.create')
            ->middlewareFor('update', 'permission:brand.update')
            ->middlewareFor('destroy', 'permission:brand.delete');

        Route::apiResource('tasks', TaskController::class)
            ->middlewareFor(['index', 'show'], 'permission:task.view')
            ->middlewareFor('store', 'permission:task.create')
            ->middlewareFor('update', 'permission:task.update')
            ->middlewareFor('destroy', 'permission:task.delete');
        Route::post('tasks/{task}/transition', [TaskController::class, 'transition'])
            ->middleware('permission:task.update');
        Route::prefix('tasks/{task}')->group(function () {
            Route::get('comments', [CollaborationController::class, 'taskComments'])->middleware('permission:task.view');
            Route::post('comments', [CollaborationController::class, 'storeTaskComment'])->middleware('permission:task.update');
            Route::post('comments/read', [CollaborationController::class, 'readTaskComments'])->middleware('permission:task.view');
            Route::get('attachments', [CollaborationController::class, 'taskAttachments'])->middleware('permission:task.view');
            Route::post('attachments', [CollaborationController::class, 'storeTaskAttachments'])->middleware('permission:task.update');
            Route::get('attachments/{attachment}/download', [CollaborationController::class, 'downloadTaskAttachment'])->middleware('permission:task.view');
            Route::delete('attachments/{attachment}', [CollaborationController::class, 'destroyTaskAttachment'])->middleware('permission:task.update');
            Route::get('secure-link', [SecureLinkController::class, 'showTaskLink'])->middleware('permission:task.view');
            Route::post('secure-link', [SecureLinkController::class, 'storeTaskLink'])->middleware('permission:task.update');
            Route::delete('secure-link', [SecureLinkController::class, 'destroyTaskLink'])->middleware('permission:task.update');
            Route::get('secure-link/access-logs', [SecureLinkController::class, 'taskAccessLogs'])->middleware('permission:task.view');
        });

        Route::apiResource('performance-reports', PerformanceReportController::class)
            ->middlewareFor(['index', 'show'], 'permission:performance-report.view')
            ->middlewareFor('store', 'permission:performance-report.create')
            ->middlewareFor('update', 'permission:performance-report.update')
            ->middlewareFor('destroy', 'permission:performance-report.delete');
        Route::post('performance-reports/{performanceReport}/transition', [PerformanceReportController::class, 'transition'])
            ->middleware('permission:performance-report.update');
        Route::post('performance-reports/{performanceReport}/versions', [PerformanceReportController::class, 'createVersion'])
            ->middleware('permission:performance-report.update');
        Route::prefix('performance-reports/{performanceReport}')->group(function () {
            Route::get('comments', [CollaborationController::class, 'reportComments'])->middleware('permission:performance-report.view');
            Route::post('comments', [CollaborationController::class, 'storeReportComment'])->middleware('permission:performance-report.update');
            Route::post('comments/read', [CollaborationController::class, 'readReportComments'])->middleware('permission:performance-report.view');
            Route::get('attachments', [CollaborationController::class, 'reportAttachments'])->middleware('permission:performance-report.view');
            Route::post('attachments', [CollaborationController::class, 'storeReportAttachments'])->middleware('permission:performance-report.update');
            Route::get('attachments/{attachment}/download', [CollaborationController::class, 'downloadReportAttachment'])->middleware('permission:performance-report.view');
            Route::delete('attachments/{attachment}', [CollaborationController::class, 'destroyReportAttachment'])->middleware('permission:performance-report.update');
            Route::get('secure-link', [SecureLinkController::class, 'showReportLink'])->middleware('permission:performance-report.view');
            Route::post('secure-link', [SecureLinkController::class, 'storeReportLink'])->middleware('permission:performance-report.update');
            Route::delete('secure-link', [SecureLinkController::class, 'destroyReportLink'])->middleware('permission:performance-report.update');
            Route::get('secure-link/access-logs', [SecureLinkController::class, 'reportAccessLogs'])->middleware('permission:performance-report.view');
        });

        Route::apiResource('users', UserController::class)
            ->middlewareFor(['index', 'show'], 'permission:user.view')
            ->middlewareFor('store', 'permission:user.create')
            ->middlewareFor('update', 'permission:user.update')
            ->middlewareFor('destroy', 'permission:user.delete');

        Route::apiResource('promotions', PromotionController::class)
            ->middlewareFor(['index', 'show'], 'permission:promotion.view')
            ->middlewareFor('store', 'permission:promotion.create')
            ->middlewareFor('update', 'permission:promotion.update')
            ->middlewareFor('destroy', 'permission:promotion.delete');

        // Product & Variant (nested)
        Route::post('products/import', [ProductController::class, 'import'])
            ->middleware('permission:product.create');
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDestroy'])
            ->middleware('permission:product.delete');
        Route::apiResource('products', ProductController::class)
            ->middlewareFor(['index', 'show'], 'permission:product.view')
            ->middlewareFor('store', 'permission:product.create')
            ->middlewareFor('update', 'permission:product.update')
            ->middlewareFor('destroy', 'permission:product.delete');
        Route::apiResource('products.variants', VariantController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middlewareFor('index', 'permission:variant.view')
            ->middlewareFor('store', 'permission:variant.create')
            ->middlewareFor('update', 'permission:variant.update')
            ->middlewareFor('destroy', 'permission:variant.delete');

        // Promotion Variant Mapping & Pricing
        Route::prefix('promotions/{promotion}/variants')->group(function () {
            Route::get('/', [PromotionVariantController::class, 'index'])
                ->middleware('permission:promotion.view');
            Route::post('/', [PromotionVariantController::class, 'store'])
                ->middleware('permission:promotion.update');
            Route::delete('/{variant}', [PromotionVariantController::class, 'destroy'])
                ->middleware('permission:promotion.update');
        });

        // Secure Links & Discussions Management (Promotions)
        Route::post('promotions/{promotion}/batch-approval', [PromotionController::class, 'batchApproval'])
            ->middleware('permission:promotion.approve');
        Route::prefix('promotions/{promotion}/secure-link')->group(function () {
            Route::get('/', [SecureLinkController::class, 'showPromotionLink'])->middleware('permission:promotion.view');
            Route::post('/', [SecureLinkController::class, 'storePromotionLink'])->middleware('permission:promotion.update');
            Route::put('/regenerate', [SecureLinkController::class, 'regeneratePromotionLink'])->middleware('permission:promotion.update');
            Route::delete('/', [SecureLinkController::class, 'destroyPromotionLink'])->middleware('permission:promotion.update');
        });
        Route::get('promotions/{promotion}/approval-histories', [SecureLinkController::class, 'getPromotionHistories'])->middleware('permission:promotion.view');
        Route::post('promotions/{promotion}/comments', [SecureLinkController::class, 'storePromotionComment'])->middleware('permission:promotion.update');
        Route::post('campaigns/{campaign}/comments', [SecureLinkController::class, 'storeCampaignComment'])->middleware('permission:campaign.update');
        Route::prefix('campaigns/{campaign}/attachments')->group(function () {
            Route::get('/', [CollaborationController::class, 'campaignAttachments'])->middleware('permission:campaign.view');
            Route::post('/', [CollaborationController::class, 'storeCampaignAttachments'])->middleware('permission:campaign.update');
            Route::get('{attachment}/download', [CollaborationController::class, 'downloadCampaignAttachment'])->middleware('permission:campaign.view');
            Route::delete('{attachment}', [CollaborationController::class, 'destroyCampaignAttachment'])->middleware('permission:campaign.update');
        });

        // Secure Links & Discussions Management (Campaigns)
        Route::prefix('campaigns/{campaign}/secure-link')->group(function () {
            Route::get('/', [SecureLinkController::class, 'showCampaignLink'])->middleware('permission:campaign.view');
            Route::post('/', [SecureLinkController::class, 'storeCampaignLink'])->middleware('permission:campaign.update');
            Route::delete('/', [SecureLinkController::class, 'destroyCampaignLink'])->middleware('permission:campaign.update');
        });
        // System Settings Management (Sprint 9)
        Route::get('/settings', [SystemSettingController::class, 'index'])->middleware('permission:settings.view');
        Route::put('/settings', [SystemSettingController::class, 'update'])->middleware('permission:settings.update');

        // ----------------------------------------
        // Sprint 11: Global Search Engine (ADR-028)
        // ----------------------------------------
        Route::get('/search', SearchController::class)->middleware('role:Super Admin|Admin');

        // ----------------------------------------
        // Sprint 11: Enterprise Audit Dashboard
        // ----------------------------------------
        Route::prefix('audit')->middleware('permission:audit.view')->group(function () {
            Route::get('/login-history', [AuditController::class, 'loginHistory']);
            Route::get('/queue-history', [AuditController::class, 'queueHistory']);
            Route::get('/error-logs', [AuditController::class, 'errorLogs']);
            Route::get('/summary', [AuditController::class, 'summary']);
        });

        // ----------------------------------------
        // Sprint 11: Notification Center (ADR-029)
        // ----------------------------------------
        Route::prefix('notifications')->middleware('permission:system.monitor')->group(function () {
            Route::get('/', [NotificationCenterController::class, 'index']);
            Route::get('/summary', [NotificationCenterController::class, 'summary']);
            Route::get('/{notification}', [NotificationCenterController::class, 'show']);
            Route::post('/{notification}/retry', [NotificationCenterController::class, 'retry']);
            Route::post('/{notification}/cancel', [NotificationCenterController::class, 'cancel']);
        });

        // ----------------------------------------
        // Sprint 11: System Monitoring Dashboard (7 categories)
        // ----------------------------------------
        Route::prefix('system')->middleware('permission:system.monitor')->group(function () {
            Route::get('/health', [SystemMonitorController::class, 'health']);
            Route::get('/queue-stats', [SystemMonitorController::class, 'queueStats']);
            Route::get('/cache-stats', [SystemMonitorController::class, 'cacheStats']);
            Route::get('/storage-stats', [SystemMonitorController::class, 'storageStats']);
            Route::get('/db-stats', [SystemMonitorController::class, 'dbStats']);
            Route::get('/metrics', [SystemMonitorController::class, 'prometheusMetrics']);
        });

        // ----------------------------------------
        // Sprint 11: Pricing Analytics & Margin Simulation
        // ----------------------------------------
        Route::prefix('analytics/pricing')->middleware('role:Super Admin|Admin')->group(function () {
            Route::get('/overview', [PricingAnalyticsController::class, 'overview']);
            Route::get('/margin-violations', [PricingAnalyticsController::class, 'marginViolations']);
            Route::post('/simulate', [PricingAnalyticsController::class, 'simulate']);
        });

        // ----------------------------------------
        // Sprint 11: Saved Filters & User Preferences
        // ----------------------------------------
        Route::prefix('saved-filters')->group(function () {
            Route::get('/', [SavedFilterController::class, 'index']);
            Route::post('/', [SavedFilterController::class, 'store']);
            Route::delete('/{id}', [SavedFilterController::class, 'destroy']);
            Route::patch('/{id}/set-default', [SavedFilterController::class, 'setDefault']);
        });
        Route::get('/me/preferences', [UserPreferenceController::class, 'show']);
        Route::put('/me/preferences', [UserPreferenceController::class, 'update']);

        // ----------------------------------------
        // Sprint 11: Business Intelligence Reports
        // ----------------------------------------
        Route::prefix('reports')->middleware('permission:report.export')->group(function () {
            Route::get('/approval-performance', [ReportController::class, 'approvalPerformance']);
            Route::get('/promotion-effectiveness', [ReportController::class, 'promotionEffectiveness']);
            Route::get('/brand-activity/{brandId}', [ReportController::class, 'brandActivity']);
        });
    });

    // ------------------------------------------
    // Public Routes (Secure Link & Monitoring)
    // ------------------------------------------
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/public/settings', [SystemSettingController::class, 'publicSettings']);
    Route::patch(
        '/public/reviews/{token}/status',
        [PublicReviewController::class, 'updateStatus']
    );

    Route::prefix('public/review/{token}')->group(function () {
        Route::get('/', [PublicReviewController::class, 'show']);
        Route::post('/identify', [PublicReviewController::class, 'identify']);
        Route::post('/approval', [PublicReviewController::class, 'approveVariant']);
        Route::post('/batch-approval', [PublicReviewController::class, 'batchApproval']);
        Route::post('/comment', [PublicReviewController::class, 'storeComment']);
        Route::get('/attachments/{attachment}/download', [PublicReviewController::class, 'downloadAttachment']);
        Route::post('/tasks/{task}/progress', [PublicReviewController::class, 'updateTaskProgress']);
        Route::post('/tasks/{task}/visual', [PublicReviewController::class, 'submitTaskVisual']);
        Route::delete('/tasks/{task}/visual', [PublicReviewController::class, 'deleteTaskVisual']);
    });
});
