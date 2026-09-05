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
            'brand_id' => ['required', 'uuid', 'exists:brands,id'],
            'campaign_id' => ['nullable', 'uuid', 'exists:campaigns,id'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'assignee_id' => ['nullable', 'uuid', 'exists:users,id'],
            'progress_status' => ['required', new Enum(TaskStatus::class)],
            'priority' => ['required', 'in:normal,mid,urgent'],
            'requires_visual' => ['boolean'],
            'visual_type' => ['nullable', 'string', 'max:100'],
            'creative_brief' => ['nullable', 'array'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'completion_summary' => ['nullable', 'string'],
            'completion_details' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $brandId = $this->input('brand_id');
        if ($brandId === null && $this->filled('campaign_id')) {
            $brandId = Campaign::query()->whereKey($this->input('campaign_id'))->value('brand_id');
        }

        $this->merge([
            'brand_id' => $brandId,
            'progress_status' => TaskStatus::Pending->value,
            'priority' => $this->input('priority', 'normal'),
        ]);
    }
}
