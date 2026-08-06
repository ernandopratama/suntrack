<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Repositories\CampaignRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PromotionRepository;
use App\Services\Cache\CacheService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RunPerformanceBenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'suntrack:benchmark-run {--json : Output results as JSON} {--output= : Save JSON results to specified file path}';

    /**
     * The console command description.
     */
    protected $description = 'Execute enterprise performance benchmarks measuring query latency, cache efficiency, and aggregation speed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Initiating SunTrack Enterprise Performance Benchmarking Suite...");
        $this->newLine();

        $company = Company::first();
        if (!$company) {
            $this->error("No Company found. Please run 'php artisan suntrack:benchmark-seed' first.");
            return Command::FAILURE;
        }

        $companyId = (int) $company->id;
        $now = Carbon::now();
        $todayStr = $now->toDateString();
        $tomorrowStr = $now->copy()->addDay()->toDateString();
        $next7DaysStr = $now->copy()->addDays(7)->toDateString();

        $cacheService = app(CacheService::class);
        $dashboardRepo = app(DashboardRepository::class);
        $productRepo = app(ProductRepository::class);
        $promotionRepo = app(PromotionRepository::class);
        $campaignRepo = app(CampaignRepository::class);

        $results = [];

        // 1. Dashboard Operational Stats (Cold Cache)
        $cacheService->flushTags(['dashboard', 'kpi', 'deadlines']);
        $start = microtime(true);
        $dashboardRepo->getKpiStats($todayStr);
        $dashboardRepo->getDeadlines($todayStr, $tomorrowStr, $next7DaysStr, $now);
        $coldTimeMs = round((microtime(true) - $start) * 1000, 2);

        $results[] = [
            'test' => 'Dashboard Operational Stats (Cold Cache / SQL Aggregation)',
            'latency_ms' => $coldTimeMs,
            'target_ms' => 100.0,
            'status' => $coldTimeMs <= 100.0 ? 'PASS' : ($coldTimeMs <= 200.0 ? 'WARN' : 'FAIL'),
        ];

        // 2. Dashboard Operational Stats (Warm Cache)
        $start = microtime(true);
        $dashboardRepo->getKpiStats($todayStr);
        $dashboardRepo->getDeadlines($todayStr, $tomorrowStr, $next7DaysStr, $now);
        $warmTimeMs = round((microtime(true) - $start) * 1000, 2);

        $results[] = [
            'test' => 'Dashboard Operational Stats (Warm Redis Tag Cache)',
            'latency_ms' => $warmTimeMs,
            'target_ms' => 15.0,
            'status' => $warmTimeMs <= 15.0 ? 'PASS' : ($warmTimeMs <= 30.0 ? 'WARN' : 'FAIL'),
        ];

        // 3. Product Catalog Search (SKU/Code Index Lookups)
        $start = microtime(true);
        $productRepo->getFilteredPaginated($companyId, ['search' => 'BENCH-50'], 15);
        $prodTimeMs = round((microtime(true) - $start) * 1000, 2);

        $results[] = [
            'test' => 'Product Catalog Search (Indexed SKU/Code Lookup + Variant Count)',
            'latency_ms' => $prodTimeMs,
            'target_ms' => 50.0,
            'status' => $prodTimeMs <= 50.0 ? 'PASS' : ($prodTimeMs <= 100.0 ? 'WARN' : 'FAIL'),
        ];

        // 4. Promotion Listing & Filtering (with Variant Aggregation)
        $start = microtime(true);
        $promotionRepo->getFilteredPaginated($companyId, ['status' => 'Approved'], 15);
        $promTimeMs = round((microtime(true) - $start) * 1000, 2);

        $results[] = [
            'test' => 'Promotion Listing (Eager Load Campaign/Brand + Variant Count)',
            'latency_ms' => $promTimeMs,
            'target_ms' => 50.0,
            'status' => $promTimeMs <= 50.0 ? 'PASS' : ($promTimeMs <= 100.0 ? 'WARN' : 'FAIL'),
        ];

        // 5. Campaign Listing & PIC Eager Loading
        $start = microtime(true);
        $campaignRepo->getFilteredPaginated($companyId, ['status' => 'Running'], 15);
        $campTimeMs = round((microtime(true) - $start) * 1000, 2);

        $results[] = [
            'test' => 'Campaign Listing (Eager Load PIC + Company Scoping)',
            'latency_ms' => $campTimeMs,
            'target_ms' => 50.0,
            'status' => $campTimeMs <= 50.0 ? 'PASS' : ($campTimeMs <= 100.0 ? 'WARN' : 'FAIL'),
        ];

        // Display results in table
        $tableData = array_map(function ($row) {
            return [
                $row['test'],
                $row['latency_ms'] . ' ms',
                '<= ' . $row['target_ms'] . ' ms',
                $row['status'] === 'PASS' ? '<info>PASS</info>' : ($row['status'] === 'WARN' ? '<comment>WARN</comment>' : '<error>FAIL</error>'),
            ];
        }, $results);

        $this->table(['Benchmark Test Case', 'Measured Latency', 'Target SLA', 'Status'], $tableData);
        $this->newLine();

        // Calculate summary metrics
        $allPassed = !in_array('FAIL', array_column($results, 'status'));
        $avgLatency = round(array_sum(array_column($results, 'latency_ms')) / count($results), 2);

        $this->info("Average Latency across test suite: {$avgLatency} ms");
        if ($allPassed) {
            $this->info("ALL BENCHMARK SLA TARGETS PASSED!");
        } else {
            $this->warn("Some benchmark targets did not meet SLA thresholds.");
        }

        if ($this->option('json') || $this->option('output')) {
            $jsonOutput = json_encode([
                'timestamp' => $now->toIso8601String(),
                'average_latency_ms' => $avgLatency,
                'sla_passed' => $allPassed,
                'benchmarks' => $results,
            ], JSON_PRETTY_PRINT);

            if ($this->option('output')) {
                File::put($this->option('output'), $jsonOutput);
                $this->info("Results saved to: " . $this->option('output'));
            }

            if ($this->option('json')) {
                $this->line($jsonOutput);
            }
        }

        return Command::SUCCESS;
    }
}
