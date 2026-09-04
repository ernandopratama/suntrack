<?php

namespace App\Observers;

use App\Models\Promotion;
use App\Services\Cache\CacheService;

class PromotionObserver
{
    public function __construct(
        protected CacheService $cache = new CacheService
    ) {}

    /**
     * Handle the Promotion "saved" event.
     */
    public function saved(Promotion $promotion): void
    {
        $this->cache->flushTags(['dashboard', 'promotions']);
    }

    /**
     * Handle the Promotion "deleted" event.
     */
    public function deleted(Promotion $promotion): void
    {
        $this->cache->flushTags(['dashboard', 'promotions']);
    }
}
