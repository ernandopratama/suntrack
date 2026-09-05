<?php

namespace App\Http\Requests;

class UpdatePerformanceReportRequest extends StorePerformanceReportRequest
{
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'required', 'uuid', 'exists:brands,id'],
            'pic_id' => ['sometimes', 'required', 'uuid', 'exists:users,id'],
            'report_type' => ['sometimes', 'required', 'in:daily,monthly'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'period_start' => ['sometimes', 'required', 'date'],
            'period_end' => ['sometimes', 'required', 'date', 'after_or_equal:period_start'],
            'executive_summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
        ];
    }
}
