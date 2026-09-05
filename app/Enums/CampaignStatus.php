<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingReview = 'waiting_review';
    case Revision = 'revision';
    case Approved = 'approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::WaitingReview => 'Waiting Review',
            self::Revision => 'Revision',
            self::Approved => 'Approved',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
