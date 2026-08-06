<?php

namespace App\Contracts\Reporting;

/**
 * Interface ReportExporterInterface
 * 
 * Defines the contract for all Reporting Exporter Adapters in SunTrack.
 * Following Refinement #6, this adapter architecture allows adding new export formats
 * (e.g., CSV, Laravel Excel, PDF, Google Sheets, API Export) without modifying core business logic.
 */
interface ReportExporterInterface
{
    /**
     * Export the generated report data into the specified format or stream.
     *
     * @param array<int, array<string, mixed>> $data The tabular or structured report data.
     * @param string $format Target format ('csv', 'excel', 'pdf', 'json', etc.).
     * @param string|null $filename Optional filename for download headers.
     * @return mixed Stream, download response, or raw string depending on driver.
     */
    public function export(array $data, string $format = 'csv', ?string $filename = null): mixed;

    /**
     * Get the supported format code for this driver (e.g., 'csv', 'excel', 'pdf').
     */
    public function getSupportedFormat(): string;
}
