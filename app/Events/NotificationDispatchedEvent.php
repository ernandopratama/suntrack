<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationDispatchedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $channel, // 'whatsapp', 'email', 'in_app', 'webhook'
        public string $recipient,
        public string $subject,
        public string $status, // 'sent_gateway_live' | 'sent_log_mode'
        public ?string $timestamp = null
    ) {
        $this->timestamp = $timestamp ?: now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationDispatched';
    }
}
