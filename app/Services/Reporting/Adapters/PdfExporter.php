<?php

namespace App\Services\Reporting\Adapters;

use App\Contracts\Reporting\ReportExporterInterface;

class PdfExporter implements ReportExporterInterface
{
    /**
     * Export tabular data to a Print-Ready Styled HTML/PDF document stream.
     * Can be opened directly in browser or converted to PDF via standard browser print engine.
     */
    public function export(array $data, string $format = 'pdf', ?string $filename = null): mixed
    {
        $filename = $filename ?: 'suntrack_report_'.date('Ymd_His').'.html';

        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');

            $htmlHeader = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SunTrack Executive Report</title>
    <style>
        body { font-family: "Inter", "Segoe UI", sans-serif; margin: 40px; color: #1e293b; background: #ffffff; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #3b82f6; padding-bottom: 15px; margin-bottom: 25px; }
        .logo { font-size: 24px; font-weight: 800; color: #1d4ed8; letter-spacing: -0.5px; }
        .meta { font-size: 12px; color: #64748b; text-align: right; }
        h1 { font-size: 20px; margin: 0 0 10px 0; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th { background: #f1f5f9; color: #334155; text-align: left; padding: 10px 12px; font-weight: 700; border-bottom: 2px solid #cbd5e1; }
        td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #475569; }
        tr:nth-child(even) { background: #f8fafc; }
        .footer { margin-top: 40px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">⚡ SunTrack</div>
        <div class="meta">
            <div>Generated: '.now()->format('d M Y, H:i').'</div>
            <div>System Operational Command Center</div>
        </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Executive Tabular Report</h1>
        <button onclick="window.print()" class="no-print" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">🖨️ Print / Save as PDF</button>
    </div>
    <table>
';

            fwrite($handle, $htmlHeader);

            if (! empty($data)) {
                fwrite($handle, "        <thead>\n            <tr>\n");
                foreach (array_keys($data[0]) as $colName) {
                    fwrite($handle, '                <th>'.htmlspecialchars((string) $colName, ENT_QUOTES, 'UTF-8')."</th>\n");
                }
                fwrite($handle, "            </tr>\n        </thead>\n        <tbody>\n");

                foreach ($data as $row) {
                    fwrite($handle, "            <tr>\n");
                    foreach ($row as $val) {
                        fwrite($handle, '                <td>'.htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8')."</td>\n");
                    }
                    fwrite($handle, "            </tr>\n");
                }
                fwrite($handle, "        </tbody>\n");
            } else {
                fwrite($handle, "        <tbody><tr><td>No data records found matching the specified criteria.</td></tr></tbody>\n");
            }

            $htmlFooter = '    </table>
    <div class="footer">
        SunTrack Enterprise Collaborative Promotion &amp; Pricing Platform &bull; Confidential
    </div>
</body>
</html>';

            fwrite($handle, $htmlFooter);
            fclose($handle);
        }, 200, $headers);
    }

    public function getSupportedFormat(): string
    {
        return 'pdf';
    }
}
