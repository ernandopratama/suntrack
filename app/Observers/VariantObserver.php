<?php

namespace App\Observers;

use App\Models\Variant;
use App\Services\Cache\CacheService;

class VariantObserver
{
    public function __construct(
        protected CacheService $cache = new CacheService
    ) {}

    /**
     * Handle the Variant "saved" event.
     */
    public function saved(Variant $variant): void
    {
        $this->cache->flushTags(['dashboard', 'products', 'variants', 'catalog']);
    }

    /**
     * Handle the Variant "deleted" event.
     */
    public function deleted(Variant $variant): void
    {
        $this->cache->flushTags(['dashboard', 'products', 'variants', 'catalog']);
    }
}
