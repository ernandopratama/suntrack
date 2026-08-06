<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'          => ['required', 'string', 'max:100'],
            'name'          => ['required', 'string', 'max:255'],
            'sku'           => ['nullable', 'string', 'max:100'],
            'normal_price'  => ['required', 'numeric', 'min:0'],
            'bottom_price'  => ['required', 'numeric', 'min:0', 'lte:normal_price'],
            'current_stock' => ['required', 'integer', 'min:0'],
            'status'        => ['required', 'in:Active,Inactive'],
        ];
    }
}
