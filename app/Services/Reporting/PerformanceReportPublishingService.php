<?php

namespace App\Services\Reporting;

use App\Enums\PerformanceReportStatus;
use App\Models\PerformanceReport;
use App\Models\SecureLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceReportPublishingService
{
    private const LINK_LIFETIME_DAYS = 7;

    private const TOKEN_BRAND_LENGTH = 24;

    private const TOKEN_CODE_LENGTH = 22;

    public function ensureDraftLink(PerformanceReport $report, User $creator): SecureLink
    {
        return DB::transaction(function () use ($report, $creator): SecureLink {
            $locked = PerformanceReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $link = $locked->secureLinks()->oldest()->first();

            return $link ?? $locked->secureLinks()->create([
                'token' => $this->makeToken($locked),
                'expires_at' => now()->addDays(self::LINK_LIFETIME_DAYS),
                'revoked_at' => now(),
                'created_by' => $creator->id,
            ]);
        });
    }

    public function publish(PerformanceReport $report, User $actor): PerformanceReport
    {
        return DB::transaction(function () use ($report, $actor): PerformanceReport {
            $locked = PerformanceReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill([
                'status' => PerformanceReportStatus::Published->value,
                'published_at' => now(),
            ])->save();
            $this->activateLinkForLockedReport($locked, $actor);

            return $locked;
        });
    }

    public function activateLink(PerformanceReport $report, User $actor): SecureLink
    {
        return DB::transaction(function () use ($report, $actor): SecureLink {
            $locked = PerformanceReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            return $this->activateLinkForLockedReport($locked, $actor);
        });
    }

    public function revokeLink(PerformanceReport $report): ?SecureLink
    {
        return DB::transaction(function () use ($report): ?SecureLink {
            $locked = PerformanceReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $link = $locked->secureLinks()->oldest()->first();
            if ($link && $link->revoked_at === null) {
                $link->forceFill(['revoked_at' => now()])->save();
            }

            return $link;
        });
    }

    private function activateLinkForLockedReport(PerformanceReport $report, User $actor): SecureLink
    {
        $link = $report->secureLinks()->oldest()->first();
        if (! $link) {
            return $report->secureLinks()->create([
                'token' => $this->makeToken($report),
                'expires_at' => now()->addDays(self::LINK_LIFETIME_DAYS),
                'created_by' => $actor->id,
            ]);
        }

        $link->forceFill([
            'expires_at' => now()->addDays(self::LINK_LIFETIME_DAYS),
            'revoked_at' => null,
        ])->save();

        return $link;
    }

    private function makeToken(PerformanceReport $report): string
    {
        $brand = trim(Str::limit(Str::slug($report->brand?->name), self::TOKEN_BRAND_LENGTH, ''), '-');
        $type = Str::slug($report->report_type);
        $date = ($report->created_at ?? now())->format('Ymd');

        return sprintf(
            '%s-%s-%s-%s',
            $brand !== '' ? $brand : 'brand',
            $type !== '' ? $type : 'report',
            $date,
            Str::random(self::TOKEN_CODE_LENGTH),
        );
    }
}
