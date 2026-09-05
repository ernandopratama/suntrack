<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case WaitingReview = 'waiting_review';
    case Revision = 'revision';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::WaitingReview => 'Waiting Review',
            self::Revision => 'Revision',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
