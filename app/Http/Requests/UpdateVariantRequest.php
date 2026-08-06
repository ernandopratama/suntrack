<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'          => ['sometimes', 'required', 'string', 'max:100'],
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'sku'           => ['nullable', 'string', 'max:100'],
            'normal_price'  => ['sometimes', 'required', 'numeric', 'min:0'],
            'bottom_price'  => ['sometimes', 'required', 'numeric', 'min:0', 'lte:normal_price'],
            'current_stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'status'        => ['sometimes', 'required', 'in:Active,Inactive'],
        ];
    }
}
