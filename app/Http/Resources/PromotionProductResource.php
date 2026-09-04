<?php

namespace App\Http\Resources;

use App\Models\Variant;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Variant */
class PromotionProductResource extends JsonResource
{
    /**
     * Transform the resource into an array for promotion product mapping.
     * Exposes only frontend-required fields and pricing snapshot.
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->resource instanceof Variant
            ? $this->resource->getRelationValue('pivot')
            : null;
        $pivot = $pivot instanceof Pivot ? $pivot : null;

        $promotionPricing = $pivot === null ? null : [
            'pivot_id' => $pivot->getAttribute('id'),
            'campaign_price' => (float) ($pivot->getAttribute('campaign_price') ?? 0),
            'bottom_price' => (float) ($pivot->getAttribute('bottom_price') ?? 0),
            'normal_price_snapshot' => (float) ($pivot->getAttribute('normal_price_snapshot') ?? 0),
            'discount_price' => (float) ($pivot->getAttribute('discount_price') ?? 0),
            'promotion_stock' => (int) ($pivot->getAttribute('promotion_stock') ?? 0),
            'purchase_limit' => (int) ($pivot->getAttribute('purchase_limit') ?? 0),
            'approval_status' => $pivot->getAttribute('approval_status') ?? 'Pending',
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
                'sku' => $this->product->sku,
            ]),
            'promotion_pricing' => $this->when($promotionPricing !== null, $promotionPricing),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
