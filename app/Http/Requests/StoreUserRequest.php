<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'type' => ['required', 'in:admin,company'],
        ];

        // Admin wajib email & password
        if ($this->input('type') === 'admin') {
            $rules['username'] = ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/', 'unique:users,username'];
            $rules['email'] = ['required', 'email', 'unique:users,email'];
            $rules['password'] = ['required', Password::defaults()];
        } else {
            // Company tidak perlu email & password
            $rules['username'] = ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/', 'unique:users,username'];
            $rules['email'] = ['nullable', 'email', 'unique:users,email'];
            $rules['password'] = ['nullable'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
        }
    }
}
