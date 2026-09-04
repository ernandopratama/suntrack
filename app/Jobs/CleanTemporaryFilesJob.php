<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanTemporaryFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info(' [Scheduler:CleanTemporaryFilesJob] Purging temporary report files older than 24 hours...');

        $disk = Storage::disk('local');
        if ($disk->exists('reports/async')) {
            $files = $disk->files('reports/async');
            $deletedCount = 0;
            $now = time();

            foreach ($files as $file) {
                // If older than 24 hours (86400 seconds)
                if ($now - $disk->lastModified($file) > 86400) {
                    $disk->delete($file);
                    $deletedCount++;
                }
            }

            Log::info(" [Scheduler:CleanTemporaryFilesJob] Purged {$deletedCount} temporary export files.");
        } else {
            Log::info(' [Scheduler:CleanTemporaryFilesJob] No temporary reports directory found. Clean.');
        }
    }
}
