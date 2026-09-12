<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PerformanceReportRules
{
    private const DAILY_BRANDS = [
        'tokopisoerdjo', 'sambalibu', 'suurlemon', 'nutripedia', 'indorganik', 'hayorganic',
    ];

    private const PERIODIC_BRANDS = [
        'tokopisoerdjo', 'sambalibu', 'suurlemon', 'nutripedia', 'indorganik', 'hayorganic',
        'pustakapeluk', 'sarmon', 'magnolia',
    ];

    /** @return array<int, string> */
    public static function typesForBrand(string $name): array
    {
        $normalized = self::normalize($name);
        if (! in_array($normalized, self::PERIODIC_BRANDS, true)) {
            return ['daily', 'weekly', 'monthly'];
        }

        $types = ['weekly', 'monthly'];
        if (in_array($normalized, self::DAILY_BRANDS, true)) {
            array_unshift($types, 'daily');
        }

        return $types;
    }

    public static function permits(string $brandName, string $reportType): bool
    {
        return in_array($reportType, self::typesForBrand($brandName), true);
    }

    private static function normalize(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9]/', '')->toString();
    }
}
