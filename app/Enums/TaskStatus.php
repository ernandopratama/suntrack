<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NotStarted = 'Not Started';
    case InProgress = 'In Progress';
    case Revision = 'Revision';
    case Completed = 'Completed';
}
