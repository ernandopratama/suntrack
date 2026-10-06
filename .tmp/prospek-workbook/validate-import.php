<?php

use App\Services\BusinessProspectImportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$path = dirname(__DIR__, 2).'/outputs/prospek-import-2026-09-28/prospek-siap-import.xlsx';
$file = new UploadedFile(
    $path,
    'prospek-siap-import.xlsx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    UPLOAD_ERR_OK,
    true,
);

$result = $app->make(BusinessProspectImportService::class)->inspect($file);

echo json_encode([
    'valid' => $result['valid'],
    'total_rows' => $result['total_rows'],
    'errors' => $result['errors'],
    'first_row' => $result['rows'][0] ?? null,
    'cleaned_row' => $result['rows'][22] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
