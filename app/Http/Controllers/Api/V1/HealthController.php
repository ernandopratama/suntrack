<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Storage\StorageService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    use ApiResponse;

    protected StorageService $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function check()
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => 'ok',
                'cache' => 'ok',
                'storage' => 'ok',
            ],
            'environment' => config('app.env'),
        ];

        // 1. Check Database
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $status['services']['database'] = 'error: '.$e->getMessage();
            $status['status'] = 'unhealthy';
            Log::error('Health check DB failure: '.$e->getMessage());
        }

        // 2. Check Cache / Redis
        try {
            Cache::put('health_ping', 'pong', 10);
            if (Cache::get('health_ping') !== 'pong') {
                throw new \Exception('Cache read mismatch.');
            }
        } catch (\Exception $e) {
            $status['services']['cache'] = 'error: '.$e->getMessage();
            $status['status'] = 'degraded';
            Log::warning('Health check Cache failure: '.$e->getMessage());
        }

        // 3. Check Storage Abstraction
        try {
            $testPath = 'health_check/ping_'.time().'.txt';
            $this->storageService->put($testPath, 'ok');
            if (! $this->storageService->exists($testPath)) {
                throw new \Exception('Storage write verified false.');
            }
            $this->storageService->delete($testPath);
        } catch (\Exception $e) {
            $status['services']['storage'] = 'error: '.$e->getMessage();
            $status['status'] = 'degraded';
            Log::warning('Health check Storage failure: '.$e->getMessage());
        }

        if ($status['status'] === 'unhealthy') {
            return $this->error("System health evaluation failed ({$status['status']}).", $status, 503);
        }

        return $this->success("System health evaluation completed ({$status['status']}).", $status, 200);
    }
}
