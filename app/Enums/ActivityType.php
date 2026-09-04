<?php

namespace App\Enums;

enum ActivityType: string
{
    case Login = 'Login';
    case Logout = 'Logout';
    case Created = 'Created';
    case Updated = 'Updated';
    case AccessUpdated = 'Access Updated';
    case StatusChanged = 'Status Changed';
    case CommentAdded = 'Comment Added';
    case FileUploaded = 'File Uploaded';
    case ApprovalSubmitted = 'Approval Submitted';
    case Deleted = 'Deleted';
}
