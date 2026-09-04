<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], // 10MB max
            'brand_id' => ['required', 'string', 'exists:brands,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload an Excel file (.xlsx or .xls).',
            'file.mimes' => 'Only XLSX or XLS files are allowed.',
            'file.max' => 'File size must not exceed 10 MB.',
            'brand_id.required' => 'Please select a brand.',
            'brand_id.exists' => 'Selected brand does not exist.',
        ];
    }
}
