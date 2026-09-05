<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth is handled by sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', new Enum(CampaignStatus::class)],
            'priority' => ['required', 'in:normal,mid,urgent'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'member_ids' => ['sometimes', 'array'],
            'member_ids.*' => ['uuid', 'distinct', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'brand_id' => ['required', 'uuid', 'exists:brands,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => CampaignStatus::Draft->value,
            'priority' => $this->input('priority', 'normal'),
        ]);
    }
}
