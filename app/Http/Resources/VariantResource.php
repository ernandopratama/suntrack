<?php

namespace App\Http\Resources;

use App\Models\Variant;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Variant */
class VariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->resource instanceof Variant
            ? $this->resource->getRelationValue('pivot')
            : null;
        $pivot = $pivot instanceof Pivot ? $pivot : null;

        $promotionPricing = $pivot === null ? null : [
            'pivot_id' => $pivot->getAttribute('id'),
            'campaign_price' => $pivot->getAttribute('campaign_price'),
            'bottom_price' => $pivot->getAttribute('bottom_price'),
            'normal_price_snapshot' => $pivot->getAttribute('normal_price_snapshot'),
            'discount_price' => $pivot->getAttribute('discount_price'),
            'promotion_stock' => $pivot->getAttribute('promotion_stock'),
            'purchase_limit' => $pivot->getAttribute('purchase_limit'),
            'approval_status' => $pivot->getAttribute('approval_status'),
            'notes' => $pivot->getAttribute('notes'),
        ];

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'code' => $this->code,
            'sku' => $this->sku,
            'name' => $this->name,
            'normal_price' => $this->normal_price,
            'bottom_price' => $this->bottom_price,
            'current_stock' => $this->current_stock,
            'status' => $this->status,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->code,
            ]),
            // Pivot pricing data (available when loaded via promotion relationship)
            'promotion_pricing' => $this->when($promotionPricing !== null, $promotionPricing),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
