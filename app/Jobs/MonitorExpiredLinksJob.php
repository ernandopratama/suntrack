<?php

namespace App\Jobs;

use App\Models\SecureLink;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorExpiredLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job: Scan for Active secure links where expires_at has passed, and update status to Expired.
     */
    public function handle(): void
    {
        Log::info(' [Scheduler:MonitorExpiredLinksJob] Scanning for expired secure public links...');

        $expiredLinks = SecureLink::where('status', 'Active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredLinks as $link) {
            $link->update(['status' => 'Expired']);

            ActivityLogger::log(
                action: 'SecureLink:AutoExpired',
                description: "Secure public link [{$link->token}] automatically marked as Expired by hourly monitoring job",
                actorType: 'System',
                actorName: 'LaravelScheduler',
                loggable: $link->linkable,
                properties: [
                    'secure_link_id' => $link->id,
                    'expired_at' => $link->expires_at->toIso8601String(),
                ]
            );
            $count++;
        }

        Log::info(" [Scheduler:MonitorExpiredLinksJob] Finished. Auto-expired {$count} links.");
    }
}
