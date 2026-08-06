<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'Draft';
    case WaitingApproval = 'Waiting Approval';
    case Approved = 'Approved';
    case Running = 'Running';
    case Finished = 'Finished';
    case Cancelled = 'Cancelled';
}
