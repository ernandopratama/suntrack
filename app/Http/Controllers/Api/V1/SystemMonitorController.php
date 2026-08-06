<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Monitoring\MetricsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * System Monitoring Dashboard Controller — 7 categories (Sprint 11 ADR-025).
 * Categories: Application | Database | Redis | Queue | Scheduler | Storage | Docker
 */
class SystemMonitorController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MetricsService $metrics = new MetricsService()
    ) {}

    /**
     * GET /api/v1/admin/system/health
     * Consolidated health check across all 7 monitoring categories.
     */
    public function health(): JsonResponse
    {
        $checks = [
            'application' => $this->checkApplication(),
            'database'    => $this->checkDatabase(),
            'redis'       => $this->checkRedis(),
            'queue'       => $this->checkQueue(),
            'storage'     => $this->checkStorage(),
        ];

        $allHealthy = !in_array('unhealthy', array_column($checks, 'status'));

        return $this->success('Health check complete.', [
            'overall_status' => $allHealthy ? 'healthy' : 'degraded',
            'checks'         => $checks,
            'timestamp'      => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    /**
     * GET /api/v1/admin/system/queue-stats
     */
    public function queueStats(): JsonResponse
    {
        $data = $this->metrics->getQueueHealthStats();
        return $this->success('Queue statistics retrieved.', $data);
    }

    /**
     * GET /api/v1/admin/system/cache-stats
     */
    public function cacheStats(): JsonResponse
    {
        $data = [
            'hit_miss_ratio' => $this->metrics->getCacheHitMissRatio(),
            'api_stats'      => $this->metrics->getApiResponseTimeStats(),
            'memory'         => $this->metrics->getMemoryUsageStats(),
            'driver'         => config('cache.default'),
        ];
        return $this->success('Cache statistics retrieved.', $data);
    }

    /**
     * GET /api/v1/admin/system/storage-stats
     */
    public function storageStats(): JsonResponse
    {
        $data = $this->metrics->getStorageStats();
        return $this->success('Storage statistics retrieved.', $data);
    }

    /**
     * GET /api/v1/admin/system/db-stats
     */
    public function dbStats(): JsonResponse
    {
        $status = $this->checkDatabase();
        return $this->success('Database statistics retrieved.', $status);
    }

    /**
     * GET /api/v1/admin/system/metrics
     * Export Prometheus-compatible metrics text.
     */
    public function prometheusMetrics(): Response
    {
        return response($this->metrics->exportPrometheusMetrics(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    // --- Private Check Methods ---

    protected function checkApplication(): array
    {
        return [
            'status'      => 'healthy',
            'app_name'    => config('app.name'),
            'app_env'     => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel'     => app()->version(),
            'memory'      => $this->metrics->getMemoryUsageStats(),
            'timestamp'   => now()->toIso8601String(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            $pdo     = DB::connection()->getPdo();
            $version = DB::selectOne('SELECT VERSION() as version');
            $tables  = count(DB::select("SHOW TABLES")) ?: 0;
            return [
                'status'        => 'healthy',
                'driver'        => DB::getDriverName(),
                'version'       => $version?->version ?? 'N/A',
                'tables'        => $tables,
                'connection'    => config('database.default'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkRedis(): array
    {
        try {
            $info = cache()->store('redis')->getRedis()->connection()->info();
            return [
                'status'            => 'healthy',
                'version'           => $info['redis_version'] ?? 'N/A',
                'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands'    => $info['total_commands_processed'] ?? 0,
                'uptime_days'       => $info['uptime_in_days'] ?? 0,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
            return [
                'status'         => $failed > 50 ? 'degraded' : 'healthy',
                'pending_jobs'   => $pending,
                'failed_jobs'    => $failed,
                'queue_driver'   => config('queue.default'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $logPath = storage_path('logs');
            $logSizeKb = file_exists($logPath . '/laravel.log')
                ? round(filesize($logPath . '/laravel.log') / 1024, 2)
                : 0.0;

            return [
                'status'          => 'healthy',
                'default_disk'    => config('filesystems.default'),
                'log_size_kb'     => $logSizeKb,
                'storage_driver'  => config('filesystems.default'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }
}
