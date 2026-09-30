<?php

namespace App\Policies;

class BusinessProspectPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'prospect';
    }
}
