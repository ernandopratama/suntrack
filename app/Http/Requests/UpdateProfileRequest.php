<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('users', 'name')->ignore($user),
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/',
                Rule::unique('users', 'username')->ignore($user),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password:web'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama tersebut sudah digunakan oleh pengguna lain.',
            'username.unique' => 'Username tersebut sudah digunakan oleh pengguna lain.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, garis bawah, dan tanda hubung.',
            'current_password.required_with' => 'Password saat ini wajib diisi untuk mengganti password.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $name = preg_replace('/\s+/', ' ', trim((string) $this->input('name')));
            $this->merge(['name' => $name]);
        }

        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
        }
    }
}
