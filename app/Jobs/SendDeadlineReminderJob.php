<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Promotion;
use App\Services\ActivityLogger;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDeadlineReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * Execute the job: Scan for campaigns and promotions ending within 24 to 48 hours.
     */
    public function handle(NotificationService $notificationService): void
    {
        Log::info(' [Scheduler:SendDeadlineReminderJob] Scanning for critical deadlines within 48 hours...');

        $startWindow = now();
        $endWindow = now()->copy()->addHours(48);

        $campaigns = Campaign::whereIn('status', ['draft', 'assigned', 'in_progress', 'Running', 'Draft'])
            ->whereBetween('end_date', [$startWindow, $endWindow])
            ->get();

        $promotions = Promotion::whereIn('status', ['Approved', 'Pending'])
            ->whereBetween('end_date', [$startWindow, $endWindow])
            ->get();

        $alertCount = 0;

        foreach ($campaigns as $camp) {
            $msg = "⚠️ Peringatan Tenggat: Campaign [{$camp->name}] akan berakhir pada {$camp->end_date->format('Y-m-d')}. Mohon tindak lanjut tim Admin.";
            $notificationService->sendReminder('in_app', 'Admin-Team', "Deadline Alert: {$camp->name}", $msg, 'Campaign', $camp->id);
            $alertCount++;
        }

        foreach ($promotions as $promo) {
            $msg = "⚠️ Peringatan Tenggat: Promotion [{$promo->code} - {$promo->name}] akan berakhir pada {$promo->end_date->format('Y-m-d')}.";
            $notificationService->sendReminder('in_app', 'Admin-Team', "Deadline Alert: {$promo->code}", $msg, 'Promotion', $promo->id);
            $alertCount++;
        }

        if ($alertCount > 0) {
            ActivityLogger::log(
                action: 'Scheduler:DeadlineReminder',
                description: "Automated deadline alerts dispatched for {$alertCount} items ending within 48 hours",
                actorType: 'System',
                actorName: 'LaravelScheduler',
                properties: ['alert_count' => $alertCount]
            );
        }

        Log::info(" [Scheduler:SendDeadlineReminderJob] Finished. Sent {$alertCount} deadline alerts.");
    }
}
