<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\Storage\StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'suntrack:backup-db {--disk= : Optional storage disk override}';

    protected $description = 'Create an automated database snapshot and archive via StorageService';

    public function handle(StorageService $storageService)
    {
        $this->info('Starting SunTrack automated database backup...');
        $startTime = microtime(true);

        try {
            $tables = [
                'users',
                'campaigns',
                'promotions',
                'products',
                'variants',
                'promotion_variant',
                'secure_links',
                'approval_histories',
                'comments',
                'system_settings',
                'login_histories',
                'activity_logs',
            ];

            $snapshot = [
                'timestamp' => now()->toIso8601String(),
                'database' => DB::connection()->getDatabaseName(),
                'tables' => [],
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $snapshot['tables'][$table] = DB::table($table)->get()->toArray();
                }
            }

            $content = json_encode($snapshot, JSON_PRETTY_PRINT);
            $filename = 'backups/suntrack_backup_'.now()->format('Y_m_d_His').'.json';

            $disk = $this->option('disk');
            if ($disk) {
                $storageService->driver($disk)->put($filename, $content);
            } else {
                $storageService->put($filename, $content);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("Backup successfully completed and saved to [{$filename}] in {$duration}ms.");

            ActivityLogger::log(
                'System:Backup',
                "Automated database backup created: {$filename}",
                'System',
                'Scheduler',
                null,
                null,
                null,
                ['filename' => $filename, 'duration_ms' => $duration, 'tables_count' => count($snapshot['tables'])]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Database backup failed: '.$e->getMessage());
            ActivityLogger::log('System:BackupFailed', 'Automated backup failed: '.$e->getMessage(), 'System', 'Scheduler');

            return Command::FAILURE;
        }
    }
}
