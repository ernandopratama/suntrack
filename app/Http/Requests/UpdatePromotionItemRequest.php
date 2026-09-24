<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePromotionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'variant_name' => ['nullable', 'string', 'max:255'],
            'normal_price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['required', 'numeric', 'min:0', 'lte:normal_price'],
            'promotion_stock' => ['nullable', 'integer', 'min:0'],
            'purchase_limit' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $stock = $this->input('promotion_stock');
                $limit = $this->input('purchase_limit');

                if ($stock !== null && $limit !== null && (int) $limit > (int) $stock) {
                    $validator->errors()->add(
                        'purchase_limit',
                        'Batas pembelian tidak boleh melebihi stok promosi.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama produk wajib diisi.',
            'normal_price.required' => 'Harga normal wajib diisi.',
            'discount_price.required' => 'Harga diskon wajib diisi.',
            'discount_price.lte' => 'Harga diskon tidak boleh melebihi harga normal.',
        ];
    }
}
