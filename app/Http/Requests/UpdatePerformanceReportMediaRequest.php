<?php

namespace App\Http\Requests;

class UpdatePerformanceReportMediaRequest extends StorePerformanceReportMediaRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
