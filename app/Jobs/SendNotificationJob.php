<?php

namespace App\Jobs;

use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     *
     * @param string $channel ('whatsapp', 'email', 'in_app', 'webhook')
     * @param string $recipient Target address
     * @param string $message Main message content
     * @param array<string, mixed> $metadata Full structured metadata
     */
    public function __construct(
        protected string $channel,
        protected string $recipient,
        protected string $message,
        protected array $metadata = []
    ) {}

    /**
     * Execute the job in the background queue.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->send($this->channel, $this->recipient, $this->message, $this->metadata);
        } catch (\Exception $e) {
            Log::error(" [Queue:SendNotificationJob] Failed to send notification via [{$this->channel}] to [{$this->recipient}]: {$e->getMessage()}");
            throw $e;
        }
    }
}
