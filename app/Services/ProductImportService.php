<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ProductImportService
{
    /**
     * Import products & variants from an XLSX file.
     *
     * Expected columns (case-insensitive):
     *   Nama Produk, Kode Produk, Nama Variasi, Kode Variasi,
     *   Harga Awal, Harga Saat Ini, Stok Saat Ini
     *
     * @return array{imported: int, updated: int, skipped: int, errors: array}
     */
    public function import(string $filePath, string $brandId, ?string $userId = null): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Cannot open XLSX file.');
        }

        // Read shared strings
        $sharedStrings = $this->parseSharedStrings($zip);

        // Read sheet data
        $rows = $this->parseSheet($zip, $sharedStrings);
        $zip->close();

        if (empty($rows)) {
            return ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['File is empty or has no data rows.']];
        }

        // Map headers (case-insensitive)
        $headerMap = $this->mapHeaders($rows[0]);
        if (! isset($headerMap['nama_produk'], $headerMap['kode_produk'])) {
            throw new \RuntimeException('Missing required columns: Nama Produk, Kode Produk.');
        }

        $brand = Brand::find($brandId);
        if (! $brand) {
            throw new \RuntimeException('Brand not found.');
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1;

                try {
                    $productName = trim($row[$headerMap['nama_produk']] ?? '');
                    $productCode = trim($row[$headerMap['kode_produk']] ?? '');

                    if (empty($productName) || empty($productCode)) {
                        $skipped++;

                        continue;
                    }

                    // Check if product exists first (more reliable than updateOrCreate)
                    $product = Product::where('code', $productCode)->first();

                    if ($product) {
                        // Update existing product
                        $product->update([
                            'name' => $productName,
                            'status' => 'Active',
                        ]);
                        $updated++;
                    } else {
                        // Create new product
                        $product = Product::create([
                            'brand_id' => $brand->id,
                            'code' => $productCode,
                            'name' => $productName,
                            'status' => 'Active',
                        ]);

                        $imported++;
                    }

                    // Update current_price if Harga Saat Ini is provided
                    if (isset($headerMap['harga_saat_ini'])) {
                        $hargaSaatIni = $this->parseNumber($row[$headerMap['harga_saat_ini']] ?? '');
                        if ($hargaSaatIni !== null) {
                            $product->current_price = $hargaSaatIni;
                        }
                    }

                    // Update description if provided
                    $product->save();

                    // Create/update variant
                    $variantCode = trim($row[$headerMap['kode_variasi'] ?? ''] ?? '');
                    $variantName = trim($row[$headerMap['nama_variasi'] ?? ''] ?? '');

                    if (empty($variantCode) && empty($variantName)) {
                        continue;
                    }

                    if (empty($variantCode)) {
                        $variantCode = $productCode.'-001';
                    }
                    if (empty($variantName)) {
                        $variantName = $productName;
                    }

                    $normalPrice = $this->parseNumber($row[$headerMap['harga_awal'] ?? ''] ?? '');
                    $bottomPrice = $this->parseNumber($row[$headerMap['harga_saat_ini'] ?? ''] ?? '');
                    $stock = $this->parseNumber($row[$headerMap['stok_saat_ini'] ?? ''] ?? '');

                    Variant::updateOrCreate(
                        ['product_id' => $product->id, 'code' => $variantCode],
                        [
                            'name' => $variantName,
                            'normal_price' => $normalPrice ?? 0,
                            'bottom_price' => $bottomPrice ?? 0,
                            'current_stock' => $stock ?? 0,
                            'status' => 'Active',
                        ]
                    );

                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    if (strlen($msg) > 200) {
                        $msg = substr($msg, 0, 200).'...';
                    } $errors[] = "Row {$rowNum}: {$msg}";
                    $skipped++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Log activity
        if ($userId && $imported > 0) {
            ActivityLogger::log(
                action: ActivityType::Created->value,
                description: "Imported {$imported} product(s) via Excel upload for brand '{$brand->name}'.",
                actorType: 'Admin',
                actorName: optional(User::find($userId))->name ?? 'System',
                loggable: $brand,
                actorId: $userId
            );
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function parseSharedStrings(ZipArchive $zip): array
    {
        $strings = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return $strings;
        }
        $sxml = simplexml_load_string($xml);
        if ($sxml === false) {
            return $strings;
        }
        foreach ($sxml->si as $si) {
            $strings[] = (string) $si->t;
        }

        return $strings;
    }

    private function parseSheet(ZipArchive $zip, array $sharedStrings): array
    {
        $rows = [];

        // --- Resolve first sheet's relationship ID ---
        $wbXml = $zip->getFromName('xl/workbook.xml');
        if ($wbXml === false) {
            return $rows;
        }
        $wb = simplexml_load_string($wbXml);
        if ($wb === false) {
            return $rows;
        }

        // Register the main SpreadsheetML namespace
        $mainNs = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rNs = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $wb->registerXPathNamespace('s', $mainNs);
        $wb->registerXPathNamespace('r', $rNs);

        // Find the first sheet
        $sheetNodes = $wb->xpath('//s:sheets/s:sheet');
        if ($sheetNodes === false || $sheetNodes === []) {
            return $rows;
        }

        // Get r:id from the first sheet
        $firstSheet = $sheetNodes[0];
        $rid = (string) $firstSheet->attributes($rNs)->id;

        if (empty($rid)) {
            return $rows;
        }

        // --- Resolve sheet path from relationships ---
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            return $rows;
        }
        $rels = simplexml_load_string($relsXml);
        if ($rels === false) {
            return $rows;
        }

        $sheetPath = null;
        foreach ($rels->Relationship as $rel) {
            $attrs = $rel->attributes();
            if ((string) $attrs['Id'] === $rid) {
                $sheetPath = 'xl/'.(string) $attrs['Target'];
                break;
            }
        }

        if (! $sheetPath) {
            return $rows;
        }

        // --- Parse sheet data ---
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            return $rows;
        }

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false) {
            return $rows;
        }

        $sheet->registerXPathNamespace('s', $mainNs);
        $sheetDataNodes = $sheet->xpath('//s:sheetData');
        if ($sheetDataNodes === false || $sheetDataNodes === []) {
            return $rows;
        }
        $sheetData = $sheetDataNodes[0];

        foreach ($sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $attrs = $cell->attributes();
                $ref = (string) $attrs['r'];
                $type = (string) $attrs['t'];
                $value = (string) $cell->v;

                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $value = $sharedStrings[(int) $value];
                }

                // Extract column letter from cell reference (e.g., "A1" -> "A")
                $col = preg_replace('/[0-9]/', '', $ref);
                $rowData[$col] = $value;
            }
            $rows[] = $rowData;
        }

        return $rows;
    }

    private function mapHeaders(array $headerRow): array
    {
        $map = [
            'nama_produk' => null,
            'kode_produk' => null,
            'nama_variasi' => null,
            'kode_variasi' => null,
            'harga_awal' => null,
            'harga_saat_ini' => null,
            'stok_saat_ini' => null,
        ];

        $colLetters = array_keys($headerRow);
        foreach ($colLetters as $col) {
            $header = strtolower(trim($headerRow[$col]));
            switch ($header) {
                case 'nama produk':
                case 'product name':
                case 'product_name':
                case 'produk':
                    $map['nama_produk'] = $col;
                    break;
                case 'kode produk':
                case 'product code':
                case 'product_code':
                case 'kode':
                    $map['kode_produk'] = $col;
                    break;
                case 'nama variasi':
                case 'variant name':
                case 'variant_name':
                case 'variasi':
                    $map['nama_variasi'] = $col;
                    break;
                case 'kode variasi':
                case 'variant code':
                case 'variant_code':
                    $map['kode_variasi'] = $col;
                    break;
                case 'harga awal':
                case 'normal price':
                case 'normal_price':
                case 'harga normal':
                    $map['harga_awal'] = $col;
                    break;
                case 'harga saat ini':
                case 'harga sekarang':
                case 'bottom price':
                case 'bottom_price':
                case 'current price':
                case 'current_price':
                    $map['harga_saat_ini'] = $col;
                    break;
                case 'stok saat ini':
                case 'current stock':
                case 'current_stock':
                case 'stock':
                case 'stok':
                    $map['stok_saat_ini'] = $col;
                    break;
            }
        }

        return $map;
    }

    private function parseNumber(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }
        // Remove currency symbols, dots (thousand separators), and spaces
        $cleaned = preg_replace('/[Rp. ,\s]/', '', $value);
        $cleaned = str_replace(',', '.', $cleaned);
        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        return null;
    }
}
