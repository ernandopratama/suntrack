<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['sometimes', 'required', 'uuid', 'exists:brands,id'],
            'campaign_id' => ['nullable', 'uuid', 'exists:campaigns,id'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'assignee_id' => ['nullable', 'uuid', 'exists:users,id'],
            'progress_status' => ['sometimes', 'required', new Enum(TaskStatus::class)],
            'priority' => ['sometimes', 'required', 'in:normal,mid,urgent'],
            'requires_visual' => ['boolean'],
            'visual_type' => ['nullable', 'string', 'max:100'],
            'creative_brief' => ['nullable', 'array'],
            'deadline' => ['nullable', 'required_with:recurrence_type', 'date'],
            'recurrence_type' => ['nullable', 'in:daily,weekly,monthly'],
            'recurrence_ends_at' => ['nullable', 'date', 'after_or_equal:deadline'],
            'recurrence_notify' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'completion_summary' => ['nullable', 'string'],
            'completion_details' => ['nullable', 'string'],
            'transition_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('progress_status')) {
            $this->merge(['progress_status' => $this->canonicalStatus((string) $this->input('progress_status'))]);
        }
        if ($this->has('recurrence_type')) {
            $this->merge(['recurrence_type' => $this->filled('recurrence_type') ? $this->input('recurrence_type') : null]);
        }
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama task',
            'brand_id' => 'brand',
            'campaign_id' => 'kampanye',
            'progress_status' => 'status',
            'priority' => 'prioritas',
            'deadline' => 'tenggat',
            'recurrence_type' => 'pola pengulangan',
            'recurrence_ends_at' => 'batas pengulangan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_with' => ':attribute wajib diisi ketika pengulangan diaktifkan.',
            'exists' => ':attribute yang dipilih tidak tersedia.',
            'in' => 'Pilihan :attribute tidak valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'after_or_equal' => ':attribute harus sama dengan atau setelah tenggat.',
        ];
    }

    private function canonicalStatus(string $status): string
    {
        return [
            'NotStarted' => 'pending',
            'Not Started' => 'pending',
            'InProgress' => 'in_progress',
            'In Progress' => 'in_progress',
            'OnHold' => 'on_hold',
            'On Hold' => 'on_hold',
            'Revision' => 'revision',
            'Completed' => 'completed',
        ][$status] ?? $status;
    }
}
