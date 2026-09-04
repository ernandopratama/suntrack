<?php

namespace App\Http\Requests;

use App\Support\Rbac\RbacRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_ids' => ['sometimes', 'array'],
            'company_ids.*' => ['uuid', 'distinct', 'exists:companies,id'],
            'brand_ids' => ['sometimes', 'array'],
            'brand_ids.*' => ['uuid', 'distinct', 'exists:brands,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(RbacRegistry::TEAM_ALLOWED_PERMISSIONS)],
            'type' => ['sometimes', Rule::in(['admin', 'team'])],
            'role' => ['sometimes', Rule::in(RbacRegistry::ROLES)],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
        }
    }
}
