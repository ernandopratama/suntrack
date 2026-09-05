<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('author_name') && $this->user()) {
            $this->merge([
                'author_name' => $this->user()->name,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'author_name' => ['required', 'string', 'max:150'],
            'author_position' => ['nullable', 'string', 'max:150'],
            'author_type' => ['nullable', 'string', 'in:Admin,Tim,Brand'],
            'parent_id' => ['nullable', 'uuid', 'exists:comments,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Komentar tidak boleh kosong.',
            'author_name.required' => 'Nama penulis komentar wajib diisi.',
        ];
    }
}
