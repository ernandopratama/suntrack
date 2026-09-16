<?php

namespace App\Http\Requests;

use App\Models\PerformanceReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedExtensions = $this->route('performanceReport') instanceof PerformanceReport
            ? 'pdf'
            : 'jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip';

        return [
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:'.$allowedExtensions],
        ];
    }

    public function messages(): array
    {
        if ($this->route('performanceReport') instanceof PerformanceReport) {
            return [
                'files.*.mimes' => 'Lampiran PMS hanya boleh berupa file PDF.',
                'files.*.max' => 'Ukuran setiap lampiran PDF maksimal 10 MB.',
            ];
        }

        return [];
    }
}
