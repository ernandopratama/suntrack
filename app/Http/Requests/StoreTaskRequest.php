<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_personal' => ['sometimes', 'boolean'],
            'brand_id' => ['nullable', 'required_unless:is_personal,true', 'uuid', 'exists:brands,id'],
            'campaign_id' => ['nullable', 'uuid', 'exists:campaigns,id'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'assignee_id' => ['nullable', 'uuid', 'exists:users,id'],
            'progress_status' => ['required', new Enum(TaskStatus::class)],
            'priority' => ['required', 'in:normal,mid,urgent'],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $isPersonal = $this->boolean('is_personal');
        $brandId = $isPersonal ? null : $this->input('brand_id');
        if (! $isPersonal && $brandId === null && $this->filled('campaign_id')) {
            $brandId = Campaign::query()->whereKey($this->input('campaign_id'))->value('brand_id');
        }

        $this->merge([
            'is_personal' => $isPersonal,
            'brand_id' => $brandId,
            'campaign_id' => $isPersonal ? null : $this->input('campaign_id'),
            'progress_status' => TaskStatus::Pending->value,
            'priority' => $this->input('priority', 'normal'),
            'recurrence_type' => $this->filled('recurrence_type') ? $this->input('recurrence_type') : null,
            'recurrence_notify' => $this->boolean('recurrence_notify', true),
        ]);
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
            'required_unless' => ':attribute wajib diisi untuk task bersama.',
            'required_with' => ':attribute wajib diisi ketika pengulangan diaktifkan.',
            'exists' => ':attribute yang dipilih tidak tersedia.',
            'in' => 'Pilihan :attribute tidak valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'after_or_equal' => ':attribute harus sama dengan atau setelah tenggat.',
        ];
    }
}
