<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class IndexCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', new Enum(CampaignStatus::class)],
            'priority' => ['nullable', Rule::in(['normal', 'mid', 'urgent'])],
            'monitoring' => ['nullable', Rule::in([
                'active',
                'completed',
                'approaching_deadline',
                'overdue',
                'waiting_review',
                'revision',
            ])],
            'sort_by' => ['nullable', Rule::in(['name', 'start_date', 'deadline', 'status', 'priority', 'created_at'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
