<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use App\Services\Cache\CacheService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkDataSeederCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'suntrack:benchmark-seed {--count=100000 : Total number of records to generate across core tables} {--chunk=2500 : Batch insert chunk size}';

    /**
     * The console command description.
     */
    protected $description = 'Seed bulk realistic benchmark dataset (±100k records) using chunked batch inserts for high-throughput performance testing.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $totalCount = (int) $this->option('count');
        $chunkSize = (int) $this->option('chunk');

        $this->info("Starting benchmark seeding of ~{$totalCount} records (chunk size: {$chunkSize})...");
        $startTime = microtime(true);

        DB::disableQueryLog();

        // Ensure prerequisite company, brand, and user exist
        $company = Company::first();
        if (!$company) {
            $company = Company::create(['name' => 'SunTrack Benchmark Corp', 'code' => 'ST-BENCH']);
        }

        $brand = Brand::where('company_id', $company->id)->first();
        if (!$brand) {
            $brand = Brand::create(['company_id' => $company->id, 'name' => 'Benchmark Brand Alpha']);
        }

        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Benchmark Admin',
                'email' => 'benchmark@suntrack.id',
                'password' => bcrypt('password'),
                'company_id' => $company->id,
            ]);
        }

        $perTableCount = (int) ceil($totalCount / 5);
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $todayStr = Carbon::now()->toDateString();
        $nextMonthStr = Carbon::now()->addDays(30)->toDateString();

        $brandId = $brand->id;
        $userId = $user->id;

        // 1. Seed Products
        $this->info("1/5 Seeding ~{$perTableCount} Products...");
        $this->seedTable('products', $perTableCount, $chunkSize, function ($index) use ($brandId, $now) {
            $id = Str::uuid()->toString();
            return [
                'id' => $id,
                'brand_id' => $brandId,
                'name' => "Benchmark Product #{$index}",
                'code' => "PRD-BENCH-{$index}",
                'sku' => "SKU-BENCH-{$index}",
                'description' => "High throughput benchmark product sample #{$index}",
                'status' => $index % 10 === 0 ? 'Inactive' : 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        // Get sample product IDs for variants
        $productIds = DB::table('products')->limit(5000)->pluck('id')->toArray();
        if (empty($productIds)) {
            $productIds = [Str::uuid()->toString()];
        }

        // 2. Seed Variants
        $this->info("2/5 Seeding ~{$perTableCount} Variants...");
        $this->seedTable('variants', $perTableCount, $chunkSize, function ($index) use ($productIds, $now) {
            $prodId = $productIds[$index % count($productIds)];
            return [
                'id' => Str::uuid()->toString(),
                'product_id' => $prodId,
                'name' => "Variant #{$index}",
                'code' => "VAR-BENCH-{$index}",
                'sku' => "VAR-SKU-{$index}",
                'normal_price' => 150000.00 + ($index % 100) * 1000,
                'bottom_price' => 120000.00 + ($index % 100) * 1000,
                'current_stock' => ($index * 7) % 500,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        // 3. Seed Campaigns
        $this->info("3/5 Seeding ~{$perTableCount} Campaigns...");
        $this->seedTable('campaigns', $perTableCount, $chunkSize, function ($index) use ($brandId, $userId, $todayStr, $nextMonthStr, $now) {
            $statuses = ['Draft', 'Running', 'Completed', 'Cancelled'];
            return [
                'id' => Str::uuid()->toString(),
                'brand_id' => $brandId,
                'name' => "Benchmark Campaign #{$index}",
                'start_date' => $todayStr . ' 08:00:00',
                'end_date' => $nextMonthStr . ' 23:59:59',
                'status' => $statuses[$index % count($statuses)],
                'pic_id' => $userId,
                'deadline' => $nextMonthStr . ' 17:00:00',
                'notes' => "Automated benchmark campaign dataset #{$index}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        // Get sample campaign IDs for promotions
        $campaignIds = DB::table('campaigns')->limit(5000)->pluck('id')->toArray();
        if (empty($campaignIds)) {
            $campaignIds = [null];
        }

        // 4. Seed Promotions
        $this->info("4/5 Seeding ~{$perTableCount} Promotions...");
        $this->seedTable('promotions', $perTableCount, $chunkSize, function ($index) use ($brandId, $campaignIds, $todayStr, $nextMonthStr, $now) {
            $statuses = ['Pending', 'Approved', 'Rejected', 'Partially Approved'];
            $campId = $campaignIds[$index % count($campaignIds)];
            return [
                'id' => Str::uuid()->toString(),
                'code' => "PRM-BENCH-{$index}",
                'brand_id' => $brandId,
                'campaign_id' => $campId,
                'name' => "Benchmark Promotion #{$index}",
                'description' => "Automated promotion dataset #{$index}",
                'start_date' => $todayStr . ' 00:00:00',
                'end_date' => $nextMonthStr . ' 23:59:59',
                'status' => $statuses[$index % count($statuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        // 5. Seed Activity Logs
        $this->info("5/5 Seeding ~{$perTableCount} Activity Logs...");
        $this->seedTable('activity_logs', $perTableCount, $chunkSize, function ($index) use ($userId, $now) {
            $actions = ['created', 'updated', 'status_changed', 'approved'];
            return [
                'id' => Str::uuid()->toString(),
                'loggable_type' => 'App\\Models\\Campaign',
                'loggable_id' => Str::uuid()->toString(),
                'action' => $actions[$index % count($actions)],
                'description' => "Benchmark system log entry #{$index}",
                'actor_type' => 'Admin',
                'actor_id' => $userId,
                'actor_name' => 'Benchmark Admin',
                'actor_position' => 'System Operator',
                'properties' => json_encode(['benchmark' => true, 'index' => $index]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        // Flush all cache tags after bulk seeding
        $this->info("Flushing Redis/Memory cache tags...");
        app(CacheService::class)->flushTags(['dashboard', 'products', 'campaigns', 'promotions', 'variants', 'catalog', 'settings']);

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Benchmark seeding completed successfully! Inserted ~" . ($perTableCount * 5) . " records in {$duration} seconds.");

        return Command::SUCCESS;
    }

    /**
     * Helper to seed a specific table in chunks.
     */
    protected function seedTable(string $table, int $total, int $chunkSize, \Closure $rowGenerator): void
    {
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        for ($i = 1; $i <= $total; $i++) {
            $batch[] = $rowGenerator($i);

            if (count($batch) >= $chunkSize || $i === $total) {
                DB::table($table)->insert($batch);
                $batch = [];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
