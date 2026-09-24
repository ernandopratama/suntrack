<?php

namespace App\Http\Resources;

use App\Models\PromotionItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PromotionItem */
class PromotionItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'promotion_id' => $this->promotion_id,
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'normal_price' => (float) $this->normal_price,
            'discount_price' => (float) $this->discount_price,
            'discount_amount' => (float) $this->discount_amount,
            'discount_percentage' => (float) $this->discount_percentage,
            'promotion_stock' => $this->promotion_stock,
            'purchase_limit' => $this->purchase_limit,
            'approval_status' => $this->approval_status,
            'rejection_notes' => $this->rejection_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
