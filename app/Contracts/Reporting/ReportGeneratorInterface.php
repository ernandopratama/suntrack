<?php

namespace App\Contracts\Reporting;

/**
 * Interface ReportGeneratorInterface
 *
 * Defines the contract for generating structured data across different reporting domains
 * in SunTrack (Campaign, Promotion, Approval, Product, Activity).
 */
interface ReportGeneratorInterface
{
    /**
     * Generate tabular or structured report data for a specific domain.
     *
     * @param  string  $reportType  The report identifier (e.g. 'campaign', 'promotion', 'approval', 'product', 'activity').
     * @param  array<string, mixed>  $filters  Optional filters (e.g., status, date_range, brand_id).
     * @return array<int, array<string, mixed>> Tabular dataset ready for export.
     */
    public function generate(string $reportType, array $filters = []): array;

    /**
     * Return a list of available report types and their descriptive titles.
     *
     * @return array<string, string> Key-value mapping of report type to human title.
     */
    public function getAvailableReportTypes(): array;
}
