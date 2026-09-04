<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrandReviewSubmittedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance for Laravel Reverb / WebSocket real-time broadcasting.
     */
    public function __construct(
        public int|string $promotionId,
        public string $promotionCode,
        public string $reviewerName,
        public string $actionType, // 'approved', 'rejected', 'batch_approved', 'comment_added'
        public ?string $timestamp = null
    ) {
        $this->timestamp = $timestamp ?: now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-dashboard'),
            new PrivateChannel('promotions.'.$this->promotionId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'BrandReviewSubmitted';
    }
}
