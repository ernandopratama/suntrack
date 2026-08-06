<?php

namespace App\Http\Requests;

use App\Models\Variant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PromotionVariantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Validate promotion-specific pricing rules.
     * All prices are stored as snapshots — they never alter the master variant.
     */
    public function rules(): array
    {
        return [
            'variant_id'       => ['required', 'exists:variants,id'],
            'campaign_price'   => ['required', 'numeric', 'min:0'],
            'bottom_price'     => ['required', 'numeric', 'min:0'],
            'discount_price'   => ['required', 'numeric', 'min:0'],
            'promotion_stock'  => ['required', 'integer', 'min:0'],
            'purchase_limit'   => ['required', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'campaign_price.min'   => 'Campaign price cannot be negative.',
            'discount_price.min'   => 'Discount price cannot be negative.',
            'promotion_stock.min'  => 'Promotion stock cannot be negative.',
            'purchase_limit.min'   => 'Purchase limit cannot be negative.',
        ];
    }

    /**
     * Configure the validator instance with business pricing rules.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $variant = Variant::find($this->input('variant_id'));
                if (!$variant) {
                    return;
                }

                $campaignPrice = (float) $this->input('campaign_price');
                $discountPrice = (float) $this->input('discount_price');
                $bottomPrice   = (float) $this->input('bottom_price', $variant->bottom_price);
                $promoStock    = (int) $this->input('promotion_stock');

                // 1. Campaign Price cannot exceed master Normal Price
                if ($campaignPrice > $variant->normal_price) {
                    $validator->errors()->add(
                        'campaign_price',
                        "Campaign price ({$campaignPrice}) cannot exceed the master Normal Price ({$variant->normal_price})."
                    );
                }

                // 2. Discount Price must respect Bottom Price floor
                if ($discountPrice < $bottomPrice) {
                    $validator->errors()->add(
                        'discount_price',
                        "Discount price ({$discountPrice}) cannot be lower than the Bottom Price ({$bottomPrice})."
                    );
                }

                // 3. Campaign Price should also not be below Bottom Price
                if ($campaignPrice < $bottomPrice) {
                    $validator->errors()->add(
                        'campaign_price',
                        "Campaign price ({$campaignPrice}) cannot be lower than the Bottom Price ({$bottomPrice})."
                    );
                }

                // 4. Promotion Stock cannot exceed available master current stock
                if ($promoStock > $variant->current_stock) {
                    $validator->errors()->add(
                        'promotion_stock',
                        "Promotion stock ({$promoStock}) cannot exceed available current stock ({$variant->current_stock})."
                    );
                }
            }
        ];
    }
}
