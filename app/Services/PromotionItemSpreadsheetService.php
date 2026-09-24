<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class PromotionItemSpreadsheetService
{
    public const HEADERS = [
        'Nama Produk',
        'Variasi',
        'Harga Normal',
        'Harga Diskon',
        'Stok Promosi',
        'Batas Pembelian',
    ];

    private const MAX_ROWS = 5000;

    public function template(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'promotion-items-');
        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat file template sementara.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Tidak dapat membuat template Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        if ($contents === false) {
            throw new RuntimeException('Tidak dapat membaca template Excel.');
        }

        return $contents;
    }

    /**
     * @return array{valid: bool, rows: array<int, array<string, mixed>>, errors: array<int, string>, total_rows: int}
     */
    public function inspect(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }

        try {
            $rows = $this->parseFirstSheet($zip);
        } finally {
            $zip->close();
        }

        if ($rows === []) {
            throw new RuntimeException('File Excel kosong.');
        }

        $headers = array_map(fn ($value) => trim((string) $value), array_slice($rows[0], 0, count(self::HEADERS)));
        $extraHeaders = array_filter(array_slice($rows[0], count(self::HEADERS)), fn ($value) => trim((string) $value) !== '');

        if ($headers !== self::HEADERS || $extraHeaders !== []) {
            throw new RuntimeException('Header template tidak sesuai. Unduh dan gunakan template terbaru tanpa mengubah nama kolom.');
        }

        $dataRows = array_slice($rows, 1);
        if (count($dataRows) > self::MAX_ROWS) {
            throw new RuntimeException('Maksimal '.self::MAX_ROWS.' baris produk dalam satu file.');
        }

        $normalized = [];
        $errors = [];
        $seen = [];

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;
            $cells = array_pad(array_slice($row, 0, count(self::HEADERS)), count(self::HEADERS), '');

            if (collect($cells)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            $productName = trim((string) $cells[0]);
            $variantName = trim((string) $cells[1]);
            $normalPrice = $this->parseNumber($cells[2]);
            $discountPrice = $this->parseNumber($cells[3]);
            $promotionStock = $this->parseInteger($cells[4]);
            $purchaseLimit = $this->parseInteger($cells[5]);
            $rowErrors = [];

            if ($productName === '') {
                $rowErrors[] = 'Nama Produk wajib diisi.';
            }
            if ($normalPrice === null || $normalPrice < 0) {
                $rowErrors[] = 'Harga Normal harus berupa angka nol atau lebih.';
            }
            if ($discountPrice === null || $discountPrice < 0) {
                $rowErrors[] = 'Harga Diskon harus berupa angka nol atau lebih.';
            }
            if ($normalPrice !== null && $discountPrice !== null && $discountPrice > $normalPrice) {
                $rowErrors[] = 'Harga Diskon tidak boleh melebihi Harga Normal.';
            }
            if ($promotionStock === false) {
                $rowErrors[] = 'Stok Promosi harus berupa bilangan bulat nol atau lebih.';
            }
            if ($purchaseLimit === false) {
                $rowErrors[] = 'Batas Pembelian harus berupa bilangan bulat nol atau lebih.';
            }
            if (is_int($promotionStock) && is_int($purchaseLimit) && $purchaseLimit > $promotionStock) {
                $rowErrors[] = 'Batas Pembelian tidak boleh melebihi Stok Promosi.';
            }

            $duplicateKey = mb_strtolower($productName).'|'.mb_strtolower($variantName);
            if ($productName !== '' && isset($seen[$duplicateKey])) {
                $rowErrors[] = "Produk dan variasi sama sudah ada pada baris {$seen[$duplicateKey]}.";
            } else {
                $seen[$duplicateKey] = $rowNumber;
            }

            if ($rowErrors !== []) {
                foreach ($rowErrors as $message) {
                    $errors[] = "Baris {$rowNumber}: {$message}";
                }
            }

            $amount = ($normalPrice ?? 0) - ($discountPrice ?? 0);
            $percentage = ($normalPrice ?? 0) > 0 ? ($amount / $normalPrice) * 100 : 0;

            $normalized[] = [
                'row_number' => $rowNumber,
                'product_name' => $productName,
                'variant_name' => $variantName !== '' ? $variantName : null,
                'normal_price' => $normalPrice,
                'discount_price' => $discountPrice,
                'discount_amount' => round($amount, 2),
                'discount_percentage' => round($percentage, 4),
                'promotion_stock' => is_int($promotionStock) ? $promotionStock : null,
                'purchase_limit' => is_int($purchaseLimit) ? $purchaseLimit : null,
                'valid' => $rowErrors === [],
                'errors' => $rowErrors,
            ];
        }

        if ($normalized === []) {
            $errors[] = 'File belum memiliki data produk.';
        }

        return [
            'valid' => $errors === [],
            'rows' => $normalized,
            'errors' => $errors,
            'total_rows' => count($normalized),
        ];
    }

    /** @return array<int, array<int, string>> */
    private function parseFirstSheet(ZipArchive $zip): array
    {
        $sharedStrings = $this->sharedStrings($zip);
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relationshipsXml === false) {
            throw new RuntimeException('Struktur workbook XLSX tidak valid.');
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);
        if (! $workbook instanceof SimpleXMLElement || ! $relationships instanceof SimpleXMLElement) {
            throw new RuntimeException('Struktur workbook XLSX tidak dapat dibaca.');
        }

        $mainNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $workbook->registerXPathNamespace('s', $mainNamespace);
        $sheets = $workbook->xpath('//s:sheets/s:sheet');
        if (! is_array($sheets) || $sheets === []) {
            return [];
        }

        $relationshipId = (string) $sheets[0]->attributes($relationshipNamespace)->id;
        $target = null;
        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                $target = (string) $relationship['Target'];
                break;
            }
        }
        if ($target === null) {
            throw new RuntimeException('Sheet pertama tidak ditemukan.');
        }

        $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            throw new RuntimeException('Data sheet pertama tidak ditemukan.');
        }

        $sheet = new \DOMDocument;
        if (! $sheet->loadXML($sheetXml)) {
            throw new RuntimeException('Data sheet pertama tidak dapat dibaca.');
        }
        $xpath = new \DOMXPath($sheet);
        $xpath->registerNamespace('s', $mainNamespace);
        $rowNodes = $xpath->query('//s:sheetData/s:row');

        $rows = [];
        foreach ($rowNodes as $rowNode) {
            $row = [];
            foreach ($xpath->query('./s:c', $rowNode) as $cell) {
                $referenceAttribute = $cell->attributes->getNamedItem('r');
                $reference = $referenceAttribute instanceof \DOMNode ? $referenceAttribute->nodeValue : 'A1';
                $column = $this->columnIndex($reference);
                $typeAttribute = $cell->attributes->getNamedItem('t');
                $type = $typeAttribute instanceof \DOMNode ? $typeAttribute->nodeValue : '';
                $valueNode = $xpath->query('./s:v', $cell)->item(0);
                $rawValue = $valueNode instanceof \DOMNode ? $valueNode->nodeValue : '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $rawValue] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = $xpath->evaluate('string(./s:is)', $cell);
                } else {
                    $value = $rawValue;
                }

                $row[$column] = $value;
            }

            if ($row !== []) {
                $maxColumn = max(array_keys($row));
                $rows[] = array_map(fn ($index) => $row[$index] ?? '', range(0, $maxColumn));
            }
        }

        return $rows;
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if (! $document instanceof SimpleXMLElement) {
            return [];
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $document->registerXPathNamespace('s', $namespace);
        $values = [];
        foreach ($document->xpath('//s:si') ?: [] as $item) {
            $item->registerXPathNamespace('s', $namespace);
            $parts = $item->xpath('.//s:t') ?: [];
            $values[] = implode('', array_map(fn (SimpleXMLElement $part) => (string) $part, $parts));
        }

        return $values;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function parseNumber(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,.-]/', '', $value) ?? '';
        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        } else {
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function parseInteger(mixed $value): int|false|null
    {
        if (trim((string) $value) === '') {
            return null;
        }
        $number = $this->parseNumber($value);
        if ($number === null || $number < 0 || floor($number) !== $number) {
            return false;
        }

        return (int) $number;
    }

    private function sheetXml(): string
    {
        $cells = '';
        foreach (self::HEADERS as $index => $header) {
            $column = chr(65 + $index);
            $escaped = htmlspecialchars($header, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $cells .= "<c r=\"{$column}1\" t=\"inlineStr\" s=\"1\"><is><t>{$escaped}</t></is></c>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:F1"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="2" width="28" customWidth="1"/><col min="3" max="4" width="18" customWidth="1"/><col min="5" max="6" width="18" customWidth="1"/></cols>'
            .'<sheetData><row r="1">'.$cells.'</row></sheetData><autoFilter ref="A1:F1"/>'
            .'</worksheet>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF293681"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>'
            .'</styleSheet>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Data Promosi" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }
}
