<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Token authorization is handled in controller/middleware
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'uuid'],
            'status' => ['required', 'string', 'in:Approved,Rejected'],
            'rejection_notes' => ['required_if:status,Rejected', 'nullable', 'string', 'max:1000'],
            'reviewer_name' => ['required', 'string', 'max:150'],
            'reviewer_position' => ['nullable', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_notes.required_if' => 'Catatan penolakan wajib diisi apabila Anda menolak variant ini.',
            'reviewer_name.required' => 'Nama reviewer wajib diisi.',
            'status.in' => 'Status approval harus Approved atau Rejected.',
        ];
    }
}
