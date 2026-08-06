<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    const CACHE_KEY = 'suntrack_system_settings';

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null, bool $isPublic = false): SystemSetting
    {
        $formattedValue = is_array($value) ? json_encode($value) : (string) $value;

        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $formattedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        $this->clearCache();

        return $setting;
    }

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = [];
            foreach (SystemSetting::all() as $setting) {
                $settings[$setting->key] = $setting->formatted_value;
            }
            return $settings;
        });
    }

    public function getGroup(string $group): array
    {
        return SystemSetting::where('group', $group)->get()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->formatted_value,
                'type' => $setting->type,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
            ];
        })->toArray();
    }

    public function getPublicSettings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY . '_public', function () {
            $settings = [];
            foreach (SystemSetting::where('is_public', true)->get() as $setting) {
                $settings[$setting->key] = $setting->formatted_value;
            }
            return $settings;
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_public');
    }
}
