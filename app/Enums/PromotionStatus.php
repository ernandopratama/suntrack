<?php

namespace App\Enums;

enum PromotionStatus: string
{
    case Pending = 'Pending';
    case PartiallyApproved = 'Partially Approved';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
}
