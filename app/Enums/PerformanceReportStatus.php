<?php

namespace App\Enums;

enum PerformanceReportStatus: string
{
    case Draft = 'draft';
    case WaitingReview = 'waiting_review';
    case Revision = 'revision';
    case Approved = 'approved';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::WaitingReview => 'Waiting Review',
            self::Revision => 'Revision',
            self::Approved => 'Approved',
            self::Published => 'Published',
        };
    }
}
