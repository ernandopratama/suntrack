<?php

namespace App\Policies;

class PromotionPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'promotion';
    }
}
