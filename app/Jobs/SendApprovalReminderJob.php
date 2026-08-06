<?php

namespace App\Jobs;

use App\Models\Promotion;
use App\Services\Notification\NotificationService;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendApprovalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * Execute the job: Scan for promotions pending approval for more than 48 hours and dispatch reminders.
     */
    public function handle(NotificationService $notificationService): void
    {
        Log::info(" [Scheduler:SendApprovalReminderJob] Scanning for pending brand review approvals...");

        // Find promotions pending for over 48 hours
        $threshold = now()->subHours(48);
        $pendingPromotions = Promotion::with(['brand', 'secureLinks'])
            ->where('status', 'Pending')
            ->where('created_at', '<=', $threshold)
            ->get();

        $remindedCount = 0;
        foreach ($pendingPromotions as $promo) {
            $brandName = $promo->brand?->name ?? 'External Brand';
            $promoCode = $promo->code ?? "PROMO-#{$promo->id}";
            $link = $promo->secureLinks->where('status', 'Active')->first();
            $reviewUrl = $link ? url("/review/{$link->token}") : url('/login');

            $message = "Halo Tim {$brandName}, mohon segera meninjau usulan promosi [{$promoCode} - {$promo->name}] di SunTrack. Klik tautan berikut untuk peninjauan: {$reviewUrl}";

            // Dispatch via WhatsApp and Email in Log Mode / Gateway Mode
            $notificationService->sendReminder('whatsapp', 'Brand-WhatsApp-Contact', 'Reminder Peninjauan Promosi', $message, 'Promotion', $promo->id);
            $notificationService->sendReminder('email', 'brand-contact@example.com', "Reminder Approval: {$promoCode}", $message, 'Promotion', $promo->id);

            $remindedCount++;
        }

        if ($remindedCount > 0) {
            ActivityLogger::log(
                action: 'Scheduler:ApprovalReminder',
                description: "Automated approval reminders dispatched for {$remindedCount} pending promotions (>48h)",
                actorType: 'System',
                actorName: 'LaravelScheduler',
                properties: ['reminded_count' => $remindedCount]
            );
        }

        Log::info(" [Scheduler:SendApprovalReminderJob] Finished. Reminders sent for {$remindedCount} promotions.");
    }
}
