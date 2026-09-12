<?php

namespace App\Http\Requests;

class UpdatePerformanceReportRequest extends StorePerformanceReportRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => array_merge(['sometimes'], $rules))
            ->all();
    }

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
