<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
}
