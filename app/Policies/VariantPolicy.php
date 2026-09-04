<?php

namespace App\Policies;

class VariantPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'variant';
    }
}
