<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Support\PerformanceReportRules;
use App\Support\RichTextSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePerformanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'uuid', 'exists:brands,id'],
            'report_type' => ['required', 'in:daily,weekly,monthly'],
            'title' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'turnover' => ['sometimes', 'numeric', 'min:0', 'max:9999999999999.99'],
            'order_count' => ['sometimes', 'integer', 'min:0'],
            'ad_spend' => ['sometimes', 'numeric', 'min:0', 'max:9999999999999.99'],
            'ad_sales' => ['sometimes', 'numeric', 'min:0', 'max:9999999999999.99'],
            'executive_summary' => ['nullable', 'string', 'max:1000000'],
            'content' => ['nullable', 'string', 'max:1000000'],
            'findings' => ['nullable', 'string', 'max:1000000'],
            'action_plan' => ['nullable', 'string', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'Brand wajib dipilih.',
            'report_type.required' => 'Jenis laporan wajib dipilih.',
            'period_end.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'turnover.min' => 'Omzet tidak boleh bernilai negatif.',
            'order_count.min' => 'Jumlah pesanan tidak boleh bernilai negatif.',
            'ad_spend.min' => 'Biaya iklan tidak boleh bernilai negatif.',
            'ad_sales.min' => 'Penjualan dari iklan tidak boleh bernilai negatif.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $values = $this->reportValues();
            if (! $values['report_type'] || ! $values['period_start'] || ! $values['period_end']) {
                return;
            }

            try {
                $start = CarbonImmutable::parse($values['period_start'])->startOfDay();
                $end = CarbonImmutable::parse($values['period_end'])->startOfDay();
            } catch (\Throwable) {
                return;
            }

            $inclusiveDays = (int) $start->diffInDays($end, false) + 1;
            if ($values['report_type'] === 'daily' && $inclusiveDays !== 1) {
                $validator->errors()->add('period_end', 'Laporan Daily hanya boleh mencakup 1 hari.');
            }
            if ($values['report_type'] === 'weekly' && ($inclusiveDays < 1 || $inclusiveDays > 7)) {
                $validator->errors()->add('period_end', 'Laporan Weekly maksimal mencakup 7 hari.');
            }
            if ($values['report_type'] === 'monthly'
                && ($inclusiveDays < 1 || $inclusiveDays > 31 || ! $start->isSameMonth($end))) {
                $validator->errors()->add('period_end', 'Laporan Monthly maksimal 31 hari dan harus berada dalam bulan yang sama.');
            }

            $brand = Brand::find($values['brand_id']);
            if ($brand && ! PerformanceReportRules::permits($brand->name, $values['report_type'])) {
                $validator->errors()->add('brand_id', "Brand {$brand->name} tidak tersedia untuk laporan {$values['report_type']}.");
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $sanitizer = app(RichTextSanitizer::class);
        $clean = [];
        foreach (['executive_summary', 'content', 'findings', 'action_plan'] as $field) {
            if ($this->exists($field)) {
                $clean[$field] = $sanitizer->clean($this->input($field));
            }
        }
        if ($clean !== []) {
            $this->merge($clean);
        }
    }

    /** @return array{brand_id:?string, report_type:?string, period_start:?string, period_end:?string} */
    protected function reportValues(): array
    {
        $report = $this->route('performanceReport');

        return [
            'brand_id' => $this->input('brand_id', $report?->brand_id),
            'report_type' => $this->input('report_type', $report?->report_type),
            'period_start' => $this->input('period_start', $report?->period_start?->format('Y-m-d')),
            'period_end' => $this->input('period_end', $report?->period_end?->format('Y-m-d')),
        ];
    }
}
