<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:100'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand_id' => ['sometimes', 'required', 'string', 'exists:brands,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:Active,Inactive'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
