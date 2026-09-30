<?php

namespace App\Services;

use App\Enums\BusinessProspectStatus;
use App\Models\BusinessProspect;
use App\Models\ProspectMarketplaceLink;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class BusinessProspectImportService
{
    private const MAX_ROWS = 5000;

    /** @return array{valid: bool, rows: array<int, array<string, mixed>>, errors: array<int, string>, total_rows: int} */
    public function inspect(UploadedFile $file): array
    {
        $rows = strtolower($file->getClientOriginalExtension()) === 'csv'
            ? $this->parseCsv($file)
            : $this->parseXlsx($file);

        $headerIndex = collect($rows)->search(fn (array $row) => collect($row)->contains(
            fn ($value) => $this->key((string) $value) === 'nama toko'
        ));
        if ($headerIndex === false) {
            throw new RuntimeException('Kolom Nama Toko tidak ditemukan pada file.');
        }

        $headers = array_map(fn ($value) => $this->key((string) $value), $rows[$headerIndex]);
        $dataRows = array_slice($rows, $headerIndex + 1);
        if (count($dataRows) > self::MAX_ROWS) {
            throw new RuntimeException('Maksimal '.self::MAX_ROWS.' baris prospek dalam satu file.');
        }

        $normalized = [];
        $errors = [];
        foreach ($dataRows as $index => $row) {
            $rowNumber = $headerIndex + $index + 2;
            $record = [];
            foreach ($headers as $column => $header) {
                if ($header !== '') {
                    $record[$header] = trim((string) ($row[$column] ?? ''));
                }
            }
            if (collect($record)->every(fn ($value) => $value === '')) {
                continue;
            }

            $name = $this->value($record, ['nama toko']);
            $links = $this->links($record);
            $rowErrors = [];
            if ($name === '') {
                $rowErrors[] = 'Nama Toko wajib diisi.';
            }
            if ($links === []) {
                $rowErrors[] = 'Minimal satu link marketplace wajib diisi.';
            }

            $existing = BusinessProspect::withTrashed()
                ->where('normalized_name', BusinessProspect::normalizeName($name))
                ->first();

            foreach ($rowErrors as $message) {
                $errors[] = "Baris {$rowNumber}: {$message}";
            }

            $normalized[] = [
                'row_number' => $rowNumber,
                'name' => $name,
                'category' => $this->value($record, ['kategori']),
                'city' => $this->value($record, ['kota']),
                'analysis_summary' => $this->value($record, ['hasil analisa tokpee', 'hasil analisa', 'analisa']),
                'analysis_link' => $this->nullable($this->value($record, ['link analisa', 'url analisa'])),
                'potential_reason' => $this->value($record, ['potensi / alasan prospek', 'potensi', 'alasan prospek']),
                'instagram_url' => $this->nullable($this->value($record, ['instagram', 'url instagram'])),
                'tiktok_url' => $this->nullable($this->value($record, ['tiktok', 'url tiktok'])),
                'facebook_or_website_url' => $this->nullable($this->value($record, ['facebook / website', 'facebook/website', 'website', 'facebook'])),
                'phone' => $this->nullable($this->value($record, ['nomor wa / telepon', 'nomor whatsapp', 'nomor wa', 'telepon'])),
                'analyzed_at' => $this->date($this->value($record, ['tanggal analisa'])),
                'status' => $this->status($this->value($record, ['status kontak', 'status'])),
                'notes' => $this->nullable($this->value($record, ['catatan'])),
                'lost_reason' => null,
                'marketplace_links' => $links,
                'existing_id' => $existing?->id,
                'existing_trashed' => $existing?->trashed() ?? false,
                'valid' => $rowErrors === [],
                'errors' => $rowErrors,
            ];
        }

        if ($normalized === []) {
            $errors[] = 'File tidak memiliki data prospek.';
        }

        return ['valid' => $errors === [], 'rows' => $normalized, 'errors' => $errors, 'total_rows' => count($normalized)];
    }

    /** @return array<int, array<int, string>> */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibuka.');
        }
        $firstLine = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn ($value) => (string) $value, $row);
        }
        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<int, string>> */
    private function parseXlsx(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }
        try {
            $shared = $this->sharedStrings($zip);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($xml === false) {
                throw new RuntimeException('Sheet pertama tidak ditemukan.');
            }
            $document = new \DOMDocument;
            $document->loadXML($xml);
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];
            foreach ($xpath->query('//s:sheetData/s:row') ?: [] as $rowNode) {
                $row = [];
                foreach ($xpath->query('./s:c', $rowNode) ?: [] as $cell) {
                    $reference = $cell instanceof \DOMElement ? $cell->getAttribute('r') : 'A1';
                    $type = $cell instanceof \DOMElement ? $cell->getAttribute('t') : '';
                    $raw = $xpath->evaluate('string(./s:v)', $cell);
                    $value = $type === 's' ? ($shared[(int) $raw] ?? '') : ($type === 'inlineStr' ? $xpath->evaluate('string(./s:is)', $cell) : $raw);
                    $row[$this->columnIndex($reference)] = $value;
                }
                if ($row !== []) {
                    $rows[] = array_map(fn ($i) => $row[$i] ?? '', range(0, max(array_keys($row))));
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
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
        $document->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return array_map(function (SimpleXMLElement $item): string {
            $item->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            return implode('', array_map(fn (SimpleXMLElement $part) => (string) $part, $item->xpath('.//s:t') ?: []));
        }, $document->xpath('//s:si') ?: []);
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $index = 0;
        foreach (str_split(strtoupper($matches[0] ?? 'A')) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    /** @param array<string, string> $record */
    private function links(array $record): array
    {
        $map = ['shopee' => ['url toko shopee', 'shopee'], 'tokopedia' => ['url toko tokopedia', 'tokopedia'], 'lazada' => ['url toko lazada', 'lazada'], 'tiktok shop' => ['url toko tiktok shop', 'tiktok shop']];
        $links = [];
        foreach ($map as $marketplace => $keys) {
            $url = $this->value($record, $keys);
            if ($url !== '') {
                $links[] = ['marketplace' => ucwords($marketplace), 'url' => $url];
            }
        }
        $genericUrl = $this->value($record, ['url marketplace', 'link marketplace']);
        if ($genericUrl !== '') {
            $links[] = ['marketplace' => $this->value($record, ['marketplace']) ?: 'Marketplace', 'url' => $genericUrl];
        }

        return collect($links)->unique(fn ($link) => ProspectMarketplaceLink::urlHash($link['url']))->values()->all();
    }

    /** @param array<string, string> $record @param array<int, string> $keys */
    private function value(array $record, array $keys): string
    {
        foreach ($keys as $key) {
            if (($record[$this->key($key)] ?? '') !== '') {
                return $record[$this->key($key)];
            }
        }

        return '';
    }

    private function status(string $status): string
    {
        $status = $this->key($status);

        return match (true) {
            str_contains($status, 'selesai') || str_contains($status, 'tidak tertarik') => BusinessProspectStatus::NotInterested->value,
            str_contains($status, 'menunggu') => BusinessProspectStatus::WaitingResponse->value,
            str_contains($status, 'follow') => BusinessProspectStatus::FollowUp->value,
            str_contains($status, 'meeting') => BusinessProspectStatus::Meeting->value,
            str_contains($status, 'proposal') => BusinessProspectStatus::Proposal->value,
            str_contains($status, 'menang') => BusinessProspectStatus::Won->value,
            str_contains($status, 'hubungi') => BusinessProspectStatus::Contacted->value,
            default => BusinessProspectStatus::New->value,
        };
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function date(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
        }
        try {
            return CarbonImmutable::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
