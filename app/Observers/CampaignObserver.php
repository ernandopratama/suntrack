<?php

namespace App\Observers;

use App\Models\Campaign;
use App\Services\Cache\CacheService;

class CampaignObserver
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Handle the Campaign "saved" event.
     */
    public function saved(Campaign $campaign): void
    {
        $this->cache->flushTags(['dashboard', 'campaigns']);
    }

    /**
     * Handle the Campaign "deleted" event.
     */
    public function deleted(Campaign $campaign): void
    {
        $this->cache->flushTags(['dashboard', 'campaigns']);
    }
}
