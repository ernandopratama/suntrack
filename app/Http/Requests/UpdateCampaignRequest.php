<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'deadline' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', new Enum(CampaignStatus::class)],
            'priority' => ['sometimes', 'required', 'in:normal,mid,urgent'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'member_ids' => ['sometimes', 'array'],
            'member_ids.*' => ['uuid', 'distinct', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'brand_id' => ['sometimes', 'required', 'uuid', 'exists:brands,id'],
            'transition_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge(['status' => $this->canonicalStatus((string) $this->input('status'))]);
        }
    }

    private function canonicalStatus(string $status): string
    {
        return [
            'Draft' => 'draft',
            'Waiting Approval' => 'waiting_review',
            'Approved' => 'approved',
            'Running' => 'in_progress',
            'Finished' => 'completed',
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
        ][$status] ?? $status;
    }
}
