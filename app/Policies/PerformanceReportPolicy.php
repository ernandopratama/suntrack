<?php

namespace App\Policies;

class PerformanceReportPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'performance-report';
    }
}
