<?php

namespace App\Http\Requests;

use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceReportMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('notes')) {
            $this->merge(['notes' => app(RichTextSanitizer::class)->clean($this->input('notes'))]);
        }
    }
}
