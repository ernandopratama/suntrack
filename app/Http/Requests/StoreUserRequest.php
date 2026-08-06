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
            $rules['email'] = ['required', 'email', 'unique:users,email'];
            $rules['password'] = ['required', Password::defaults()];
        } else {
            // Company tidak perlu email & password
            $rules['email'] = ['nullable', 'email', 'unique:users,email'];
            $rules['password'] = ['nullable'];
        }

        return $rules;
    }
}
