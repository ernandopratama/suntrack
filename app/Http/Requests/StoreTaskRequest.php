<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recurrence_time' => ['nullable', 'date_format:H:i'],
            'recurrence_weekdays' => ['nullable', 'array', 'min:1'],
            'recurrence_weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'recurrence_month_day' => [
                'nullable',
                Rule::in(array_merge(['last'], array_map('strval', range(1, 31)))),
            ],
            'recurrence_ends_at' => ['nullable', 'date', 'after_or_equal:deadline'],
            'recurrence_max_occurrences' => ['nullable', 'integer', 'min:1', 'max:1000'],
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

        $recurrenceType = $this->filled('recurrence_type') ? $this->input('recurrence_type') : null;
        $weekdays = collect($this->input('recurrence_weekdays', []))
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->merge([
            'is_personal' => $isPersonal,
            'brand_id' => $brandId,
            'campaign_id' => $isPersonal ? null : $this->input('campaign_id'),
            'progress_status' => TaskStatus::Pending->value,
            'priority' => $this->input('priority', 'normal'),
            'recurrence_type' => $recurrenceType,
            'recurrence_interval' => $recurrenceType ? max(1, (int) $this->input('recurrence_interval', 1)) : 1,
            'recurrence_time' => $recurrenceType && $this->filled('recurrence_time') ? $this->input('recurrence_time') : null,
            'recurrence_weekdays' => $recurrenceType === 'weekly' && $weekdays !== [] ? $weekdays : null,
            'recurrence_month_day' => $recurrenceType === 'monthly' && $this->filled('recurrence_month_day')
                ? (string) $this->input('recurrence_month_day')
                : null,
            'recurrence_ends_at' => $recurrenceType && $this->filled('recurrence_ends_at') ? $this->input('recurrence_ends_at') : null,
            'recurrence_max_occurrences' => $recurrenceType && $this->filled('recurrence_max_occurrences')
                ? (int) $this->input('recurrence_max_occurrences')
                : null,
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
            'recurrence_interval' => 'interval pengulangan',
            'recurrence_time' => 'waktu pengulangan',
            'recurrence_weekdays' => 'hari pengulangan',
            'recurrence_month_day' => 'tanggal pengulangan bulanan',
            'recurrence_ends_at' => 'batas pengulangan',
            'recurrence_max_occurrences' => 'jumlah pengulangan',
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
            'between' => ':attribute harus berada dalam rentang :min sampai :max.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'date_format' => ':attribute harus menggunakan format jam yang valid.',
        ];
    }
}
