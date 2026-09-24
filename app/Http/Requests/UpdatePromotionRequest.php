<?php

namespace App\Http\Requests;

use App\Enums\PromotionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePromotionRequest extends FormRequest
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
            'campaign_id' => [
                'sometimes',
                'required',
                'exists:campaigns,id',
                Rule::unique('promotions', 'campaign_id')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('promotion')),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'required', new Enum(PromotionStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'campaign_id.unique' => 'Kampanye ini sudah memiliki promosi.',
        ];
    }
}
