<?php

namespace App\Policies;

class CampaignPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'campaign';
    }
}
