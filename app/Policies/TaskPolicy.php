<?php

namespace App\Policies;

class TaskPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'task';
    }
}
