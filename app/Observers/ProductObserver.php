<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Cache\CacheService;

class ProductObserver
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Handle the Product "saved" event.
     */
    public function saved(Product $product): void
    {
        $this->cache->flushTags(['dashboard', 'products', 'catalog']);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->cache->flushTags(['dashboard', 'products', 'catalog']);
    }
}
