<?php

namespace App\Policies;

class ProductPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'product';
    }
}
