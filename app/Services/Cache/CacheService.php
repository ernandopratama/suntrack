<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache a value with tags and TTL (in seconds). Gracefully falls back if tags are unsupported.
     */
    public function remember(array|string $tags, string $key, int $ttlSeconds, \Closure $callback): mixed
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        try {
            if ($this->supportsTags()) {
                return Cache::tags($tags)->remember($key, $ttlSeconds, $callback);
            }
            return Cache::remember($key, $ttlSeconds, $callback);
        } catch (\Throwable $e) {
            Log::warning("CacheService remember error for key [{$key}]: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Cache a value forever with tags.
     */
    public function rememberForever(array|string $tags, string $key, \Closure $callback): mixed
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        try {
            if ($this->supportsTags()) {
                return Cache::tags($tags)->rememberForever($key, $callback);
            }
            return Cache::rememberForever($key, $callback);
        } catch (\Throwable $e) {
            Log::warning("CacheService rememberForever error for key [{$key}]: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Forget a specific cache key within tags.
     */
    public function forget(array|string $tags, string $key): bool
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        try {
            if ($this->supportsTags()) {
                return (bool) Cache::tags($tags)->forget($key);
            }
            return (bool) Cache::forget($key);
        } catch (\Throwable $e) {
            Log::warning("CacheService forget error for key [{$key}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Flush all cache keys associated with the given tag(s).
     */
    public function flushTags(array|string $tags): bool
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        try {
            if ($this->supportsTags()) {
                return (bool) Cache::tags($tags)->flush();
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning("CacheService flushTags error for tags [" . implode(',', $tags) . "]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if current cache driver supports tagging.
     */
    protected function supportsTags(): bool
    {
        try {
            return Cache::getStore() instanceof \Illuminate\Cache\TaggedCache
                || method_exists(Cache::getStore(), 'tags')
                || (method_exists(Cache::getFacadeRoot(), 'supportsTags') && Cache::supportsTags());
        } catch (\Throwable $e) {
            return false;
        }
    }
}
