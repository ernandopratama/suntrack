<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'pic_id' => ['required', 'uuid', 'exists:users,id'],
            'report_type' => ['required', 'in:daily,monthly'],
            'title' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'executive_summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
        ];
    }
}
