<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification Log — 6-stage delivery lifecycle model (ADR-029 / Sprint 11).
 *
 * Status lifecycle: pending → processing → sent → delivered → failed | cancelled
 */
class NotificationLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'type', 'recipient', 'subject', 'body', 'status',
        'attempts', 'max_attempts',
        'processing_at', 'sent_at', 'delivered_at', 'failed_at', 'cancelled_at', 'scheduled_at',
        'failure_reason', 'metadata',
        'notifiable_type', 'notifiable_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processing_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /** Allowed status values */
    public const STATUSES = ['pending', 'processing', 'sent', 'delivered', 'failed', 'cancelled'];

    public function notifiable()
    {
        return $this->morphTo();
    }

    /** Transition to processing state */
    public function markProcessing(): void
    {
        $this->update(['status' => 'processing', 'processing_at' => now()]);
    }

    /** Transition to sent state */
    public function markSent(?array $meta = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => $meta ?? $this->metadata,
        ]);
    }

    /** Transition to delivered state (from webhook callback) */
    public function markDelivered(?array $meta = null): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'metadata' => $meta ?? $this->metadata,
        ]);
    }

    /** Transition to failed state */
    public function markFailed(string $reason, ?array $meta = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
            'metadata' => $meta ?? $this->metadata,
        ]);
    }

    /** Cancel a pending notification */
    public function markCancelled(): void
    {
        $this->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /** Increment attempt counter */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /** Check if retry is allowed */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->attempts < $this->max_attempts;
    }
}
