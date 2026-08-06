<?php

namespace App\Observers;

use App\Models\SystemSetting;
use App\Services\Cache\CacheService;
use App\Services\Settings\SettingsService;

class SystemSettingObserver
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Handle the SystemSetting "saved" event.
     */
    public function saved(SystemSetting $setting): void
    {
        $this->cache->flushTags(['settings']);
        app(SettingsService::class)->clearCache();
    }

    /**
     * Handle the SystemSetting "deleted" event.
     */
    public function deleted(SystemSetting $setting): void
    {
        $this->cache->flushTags(['settings']);
        app(SettingsService::class)->clearCache();
    }
}
