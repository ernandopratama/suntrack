<?php

namespace App\Http\Requests;

use App\Enums\PromotionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePromotionRequest extends FormRequest
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
            'campaign_id' => [
                'required',
                'exists:campaigns,id',
                Rule::unique('promotions', 'campaign_id')->whereNull('deleted_at'),
            ],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', new Enum(PromotionStatus::class)],
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
