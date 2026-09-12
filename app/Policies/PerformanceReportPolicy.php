<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Illuminate\Database\Eloquent\Model;

class PerformanceReportPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'performance-report';
    }

    public function update(User $user, Model $performanceReport): bool
    {
        return $user->can('performance-report.update')
            && $this->dataScope->canAccess($user, $performanceReport)
            && ($performanceReport->created_by === $user->id || $user->hasRole(RbacRegistry::SUPER_ADMIN));
    }

    public function delete(User $user, Model $performanceReport): bool
    {
        return $user->can('performance-report.delete')
            && $this->dataScope->canAccess($user, $performanceReport)
            && ($performanceReport->created_by === $user->id || $user->hasRole(RbacRegistry::SUPER_ADMIN));
    }
}
