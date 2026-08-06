<?php

namespace App\Repositories;

use App\Models\LoginHistory;
use App\Services\Cache\CacheService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Audit Repository — aggregates all historical audit event data sources (Sprint 11).
 */
class AuditRepository
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Retrieve paginated login history with optional filters.
     */
    public function getLoginHistory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = LoginHistory::with('user')->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', '%' . $filters['ip_address'] . '%');
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Retrieve paginated queue job history (pending + failed jobs).
     */
    public function getQueueHistory(array $filters = [], int $perPage = 20): array
    {
        $failed = DB::table('failed_jobs')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($j) => [
                'id'           => $j->id,
                'queue'        => $j->queue,
                'job_class'    => class_basename(json_decode($j->payload, true)['displayName'] ?? 'Unknown'),
                'status'       => 'failed',
                'error'        => mb_strimwidth($j->exception, 0, 200, '...'),
                'failed_at'    => $j->failed_at,
            ])->values()->all();

        $pending = DB::table('jobs')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($j) => [
                'id'         => $j->id,
                'queue'      => $j->queue,
                'job_class'  => class_basename(json_decode($j->payload, true)['displayName'] ?? 'Unknown'),
                'status'     => $j->reserved_at ? 'processing' : 'pending',
                'attempts'   => $j->attempts,
                'created_at' => date('Y-m-d H:i:s', $j->created_at),
            ])->values()->all();

        return [
            'failed'  => $failed,
            'pending' => $pending,
            'summary' => [
                'total_pending' => DB::table('jobs')->count(),
                'total_failed'  => DB::table('failed_jobs')->count(),
            ],
        ];
    }

    /**
     * Retrieve application audit summary KPIs (cached for 60 seconds).
     */
    public function getAuditSummary(): array
    {
        return $this->cache->remember(['audit', 'dashboard'], 'audit_summary_' . now()->format('Y-m-d-H'), 60, function () {
            $todayStr = now()->toDateString();
            return [
                'logins_today'          => LoginHistory::whereDate('created_at', $todayStr)->count(),
                'failed_logins_today'   => LoginHistory::whereDate('created_at', $todayStr)->where('status', 'failed')->count(),
                'total_failed_jobs'     => DB::table('failed_jobs')->count(),
                'total_pending_jobs'    => DB::table('jobs')->count(),
                'error_log_size_kb'     => $this->getErrorLogSizeKb(),
            ];
        });
    }

    /**
     * Read last N lines from the Laravel error log file.
     */
    public function getErrorLogs(int $lines = 50): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return ['logs' => [], 'size_kb' => 0];
        }

        $content = File::get($logPath);
        $allLines = array_filter(explode("\n", $content));
        $recent   = array_slice($allLines, -$lines);

        return [
            'logs'    => array_values($recent),
            'size_kb' => round(File::size($logPath) / 1024, 2),
        ];
    }

    protected function getErrorLogSizeKb(): float
    {
        $logPath = storage_path('logs/laravel.log');
        return File::exists($logPath) ? round(File::size($logPath) / 1024, 2) : 0.0;
    }
}
