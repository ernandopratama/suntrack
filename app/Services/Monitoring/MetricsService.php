<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enterprise Observability & Performance Monitoring Abstraction Layer (ADR-025).
 * Prepared for future integration with Prometheus, Grafana, OpenTelemetry, or Laravel Pulse.
 */
class MetricsService
{
    protected string $prefix = 'monitoring_metrics_';

    /**
     * Record HTTP API request latency and HTTP status code.
     */
    public function recordRequestLatency(string $endpoint, float $durationMs, int $statusCode): void
    {
        try {
            $key = "{$this->prefix}api_requests";
            $metrics = Cache::get($key, ['total_requests' => 0, 'total_duration_ms' => 0.0, 'status_codes' => []]);

            $metrics['total_requests']++;
            $metrics['total_duration_ms'] += $durationMs;
            $metrics['status_codes'][$statusCode] = ($metrics['status_codes'][$statusCode] ?? 0) + 1;

            Cache::put($key, $metrics, now()->addDays(1));

            if ($durationMs > 500) {
                Log::warning("High Request Latency Detected: [{$endpoint}] took {$durationMs}ms (Status: {$statusCode})");
            }
        } catch (\Throwable $e) {
            Log::error('MetricsService recordRequestLatency error: '.$e->getMessage());
        }
    }

    /**
     * Record a cache hit event.
     */
    public function recordCacheHit(string $tagOrKey = 'general'): void
    {
        $this->incrementCacheCount('hits', $tagOrKey);
    }

    /**
     * Record a cache miss event.
     */
    public function recordCacheMiss(string $tagOrKey = 'general'): void
    {
        $this->incrementCacheCount('misses', $tagOrKey);
    }

    /**
     * Get Cache Hit/Miss ratio statistics.
     *
     * @return array{hits: int, misses: int, ratio: float}
     */
    public function getCacheHitMissRatio(): array
    {
        try {
            $hits = (int) Cache::get("{$this->prefix}cache_hits", 0);
            $misses = (int) Cache::get("{$this->prefix}cache_misses", 0);
            $total = $hits + $misses;
            $ratio = $total > 0 ? round(($hits / $total) * 100, 2) : 100.0;

            return [
                'hits' => $hits,
                'misses' => $misses,
                'ratio' => $ratio,
            ];
        } catch (\Throwable $e) {
            return ['hits' => 0, 'misses' => 0, 'ratio' => 0.0];
        }
    }

    /**
     * Record background queue job execution metrics.
     */
    public function recordQueueMetric(string $jobName, float $executionMs, string $status = 'success'): void
    {
        try {
            $key = "{$this->prefix}queue_jobs";
            $history = Cache::get($key, []);

            array_unshift($history, [
                'job' => $jobName,
                'duration_ms' => round($executionMs, 2),
                'status' => $status,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Keep only latest 100 job metrics in cache
            Cache::put($key, array_slice($history, 0, 100), now()->addDays(7));
        } catch (\Throwable $e) {
            Log::error('MetricsService recordQueueMetric error: '.$e->getMessage());
        }
    }

    /**
     * Record scheduled cron task execution history.
     */
    public function recordSchedulerExecution(string $command, float $durationMs, string $status = 'success'): void
    {
        try {
            $key = "{$this->prefix}scheduler_history";
            $history = Cache::get($key, []);

            array_unshift($history, [
                'command' => $command,
                'duration_ms' => round($durationMs, 2),
                'status' => $status,
                'timestamp' => now()->toIso8601String(),
            ]);

            Cache::put($key, array_slice($history, 0, 50), now()->addDays(30));
        } catch (\Throwable $e) {
            Log::error('MetricsService recordSchedulerExecution error: '.$e->getMessage());
        }
    }

    /**
     * Retrieve system memory usage diagnostics.
     *
     * @return array{current_mb: float, peak_mb: float, memory_limit: string}
     */
    public function getMemoryUsageStats(): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit') ?: 'N/A',
        ];
    }

    /**
     * Retrieve aggregated API response time statistics for dashboard display.
     *
     * @return array<string, mixed>
     */
    public function getApiResponseTimeStats(): array
    {
        try {
            $metrics = Cache::get("{$this->prefix}api_requests", ['total_requests' => 0, 'total_duration_ms' => 0.0, 'status_codes' => []]);
            $totalReqs = max(1, (int) $metrics['total_requests']);
            $avgLatency = round((float) $metrics['total_duration_ms'] / $totalReqs, 2);

            return [
                'total_requests' => $metrics['total_requests'],
                'average_latency_ms' => $avgLatency,
                'status_codes' => $metrics['status_codes'],
                'target_sla_ms' => 50.0,
                'status' => $avgLatency <= 50.0 ? 'Optimal' : 'Degraded',
            ];
        } catch (\Throwable $e) {
            return ['total_requests' => 0, 'average_latency_ms' => 0.0, 'status_codes' => [], 'status' => 'Unknown'];
        }
    }

    /**
     * Export metrics in Prometheus / OpenTelemetry text format for external scrapers.
     */
    public function exportPrometheusMetrics(): string
    {
        $cacheRatio = $this->getCacheHitMissRatio();
        $apiStats = $this->getApiResponseTimeStats();
        $memory = $this->getMemoryUsageStats();

        $lines = [
            '# HELP suntrack_api_requests_total Total HTTP API requests executed',
            '# TYPE suntrack_api_requests_total counter',
            "suntrack_api_requests_total {$apiStats['total_requests']}",
            '',
            '# HELP suntrack_api_latency_avg_ms Average API request latency in milliseconds',
            '# TYPE suntrack_api_latency_avg_ms gauge',
            "suntrack_api_latency_avg_ms {$apiStats['average_latency_ms']}",
            '',
            '# HELP suntrack_cache_hit_ratio Redis/Memory cache hit ratio percentage',
            '# TYPE suntrack_cache_hit_ratio gauge',
            "suntrack_cache_hit_ratio {$cacheRatio['ratio']}",
            '',
            '# HELP suntrack_memory_usage_mb Current PHP process memory usage in MB',
            '# TYPE suntrack_memory_usage_mb gauge',
            "suntrack_memory_usage_mb {$memory['current_mb']}",
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * Retrieve background queue health statistics.
     *
     * @return array<string, mixed>
     */
    public function getQueueHealthStats(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $processing = DB::table('jobs')->whereNotNull('reserved_at')->count();
            $history = Cache::get("{$this->prefix}queue_jobs", []);

            return [
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'processing_jobs' => $processing,
                'queue_driver' => config('queue.default'),
                'recent_history' => array_slice($history, 0, 10),
                'status' => $failed > 50 ? 'degraded' : 'healthy',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    /**
     * Retrieve storage disk usage statistics.
     *
     * @return array<string, mixed>
     */
    public function getStorageStats(): array
    {
        $logPath = storage_path('logs/laravel.log');
        $logSizeKb = file_exists($logPath) ? round(filesize($logPath) / 1024, 2) : 0.0;

        return [
            'default_disk' => config('filesystems.default'),
            'log_size_kb' => $logSizeKb,
            'storage_path' => storage_path(),
            'public_path' => public_path(),
        ];
    }

    protected function incrementCacheCount(string $type, string $tagOrKey): void
    {
        try {
            $key = "{$this->prefix}cache_{$type}";
            $val = (int) Cache::get($key, 0);
            Cache::put($key, $val + 1, now()->addDays(7));
        } catch (\Throwable $e) {
            // Ignore cache errors in metric increment
        }
    }
}
