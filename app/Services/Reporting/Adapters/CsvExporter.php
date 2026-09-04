<?php

namespace App\Services\Reporting\Adapters;

use App\Contracts\Reporting\ReportExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter implements ReportExporterInterface
{
    /**
     * Export tabular data to a CSV StreamedResponse.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return StreamedResponse|string
     */
    public function export(array $data, string $format = 'csv', ?string $filename = null): mixed
    {
        $filename = $filename ?: 'report_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            if (! empty($data)) {
                // Write headers from first row keys
                fputcsv($handle, array_keys($data[0]));

                // Write data rows
                foreach ($data as $row) {
                    fputcsv($handle, array_values($row));
                }
            } else {
                fputcsv($handle, ['No data available for the selected filters.']);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function getSupportedFormat(): string
    {
        return 'csv';
    }
}
