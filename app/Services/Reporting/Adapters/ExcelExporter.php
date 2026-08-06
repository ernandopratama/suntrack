<?php

namespace App\Services\Reporting\Adapters;

use App\Contracts\Reporting\ReportExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter implements ReportExporterInterface
{
    /**
     * Export tabular data to an XML SpreadsheetML (.xls/.xlsx) StreamedResponse.
     * Compatible with Microsoft Excel, Google Sheets, and LibreOffice with zero memory bloat.
     */
    public function export(array $data, string $format = 'excel', ?string $filename = null): mixed
    {
        $filename = $filename ?: 'suntrack_report_' . date('Ymd_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // XML Spreadsheet 2003 Header with styling
            $xmlHeader = '<?xml version="1.0"?>' . "\n" .
                '<?mso-application progid="Excel.Sheet"?>' . "\n" .
                '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n" .
                ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n" .
                ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n" .
                ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n" .
                ' <Styles>' . "\n" .
                '  <Style ss:ID="Header">' . "\n" .
                '   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Calibri" ss:Size="11"/>' . "\n" .
                '   <Interior ss:Color="#1E3A8A" ss:Pattern="Solid"/>' . "\n" .
                '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n" .
                '  </Style>' . "\n" .
                '  <Style ss:ID="Data">' . "\n" .
                '   <Font ss:FontName="Calibri" ss:Size="10"/>' . "\n" .
                '   <Alignment ss:Vertical="Center"/>' . "\n" .
                '  </Style>' . "\n" .
                ' </Styles>' . "\n" .
                ' <Worksheet ss:Name="SunTrack Report">' . "\n" .
                '  <Table>' . "\n";
            
            fwrite($handle, $xmlHeader);

            if (!empty($data)) {
                // Write headers
                fwrite($handle, '   <Row ss:Height="24">' . "\n");
                foreach (array_keys($data[0]) as $colName) {
                    $cleanCol = htmlspecialchars((string) $colName, ENT_XML1, 'UTF-8');
                    fwrite($handle, '    <Cell ss:StyleID="Header"><Data ss:Type="String">' . $cleanCol . '</Data></Cell>' . "\n");
                }
                fwrite($handle, '   </Row>' . "\n");

                // Write rows
                foreach ($data as $row) {
                    fwrite($handle, '   <Row ss:Height="20">' . "\n");
                    foreach ($row as $val) {
                        $cleanVal = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                        $type = is_numeric($val) && !preg_match('/^0\d+/', (string)$val) ? 'Number' : 'String';
                        fwrite($handle, '    <Cell ss:StyleID="Data"><Data ss:Type="' . $type . '">' . $cleanVal . '</Data></Cell>' . "\n");
                    }
                    fwrite($handle, '   </Row>' . "\n");
                }
            } else {
                fwrite($handle, '   <Row><Cell><Data ss:Type="String">No data available.</Data></Cell></Row>' . "\n");
            }

            $xmlFooter = '  </Table>' . "\n" .
                ' </Worksheet>' . "\n" .
                '</Workbook>';

            fwrite($handle, $xmlFooter);
            fclose($handle);
        }, 200, $headers);
    }

    public function getSupportedFormat(): string
    {
        return 'excel';
    }
}
