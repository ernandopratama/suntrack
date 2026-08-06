<?php

namespace App\Services;

use App\Models\SavedFilter;
use App\Models\UserPreference;
use App\Services\Cache\CacheService;
use Illuminate\Support\Collection;

/**
 * User Preference Service — manages per-user personalization settings and saved filters (Sprint 11).
 */
class UserPreferenceService
{
    public function __construct(
        protected CacheService $cache = new CacheService()
    ) {}

    /**
     * Get or create user preferences.
     */
    public function getPreferences(string $userId): UserPreference
    {
        return $this->cache->remember(
            ['user_preferences'],
            "user_pref_{$userId}",
            3600,
            fn () => UserPreference::firstOrCreate(['user_id' => $userId], $this->defaults())
        );
    }

    /**
     * Update user preferences.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePreferences(string $userId, array $data): UserPreference
    {
        $pref = UserPreference::updateOrCreate(['user_id' => $userId], array_intersect_key($data, array_flip([
            'default_landing_page', 'default_page_size', 'theme', 'locale', 'timezone',
            'dashboard_widgets', 'extended',
        ])));

        $this->cache->flushTags(['user_preferences']);
        return $pref->fresh();
    }

    /**
     * Get saved filters for a user and module.
     *
     * @return Collection<int, SavedFilter>
     */
    public function getSavedFilters(string $userId, string $module): Collection
    {
        return SavedFilter::where('user_id', $userId)
            ->where('module', $module)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Save a new filter for a user and module.
     *
     * @param  array<string, mixed>  $filters
     */
    public function saveFilter(string $userId, string $module, string $name, array $filters, bool $isDefault = false): SavedFilter
    {
        if ($isDefault) {
            // Unset previous default for this module
            SavedFilter::where('user_id', $userId)->where('module', $module)->update(['is_default' => false]);
        }

        return SavedFilter::create([
            'user_id'    => $userId,
            'module'     => $module,
            'name'       => $name,
            'filters'    => $filters,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Set a filter as default, unsetting any previous default for the module.
     */
    public function setDefaultFilter(string $filterId, string $userId): SavedFilter
    {
        $filter = SavedFilter::where('id', $filterId)->where('user_id', $userId)->firstOrFail();
        SavedFilter::where('user_id', $userId)->where('module', $filter->module)->update(['is_default' => false]);
        $filter->update(['is_default' => true]);
        return $filter->fresh();
    }

    /**
     * Delete a saved filter belonging to the user.
     */
    public function deleteFilter(string $filterId, string $userId): bool
    {
        return (bool) SavedFilter::where('id', $filterId)->where('user_id', $userId)->delete();
    }

    protected function defaults(): array
    {
        return [
            'default_landing_page' => '/dashboard',
            'default_page_size'    => 15,
            'theme'                => 'dark',
            'locale'               => 'id',
            'timezone'             => 'Asia/Jakarta',
            'dashboard_widgets'    => null,
            'extended'             => null,
        ];
    }
}
