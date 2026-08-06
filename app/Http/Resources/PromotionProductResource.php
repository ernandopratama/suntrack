<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionProductResource extends JsonResource
{
    /**
     * Transform the resource into an array for promotion product mapping.
     * Exposes only frontend-required fields and pricing snapshot.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'code'          => $this->code,
            'sku'           => $this->sku,
            'name'          => $this->name,
            'normal_price'  => $this->normal_price,
            'bottom_price'  => $this->bottom_price,
            'current_stock' => $this->current_stock,
            'status'        => $this->status,
            'product'       => $this->whenLoaded('product', fn() => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->code,
                'sku'  => $this->product->sku,
            ]),
            'promotion_pricing' => $this->when(
                $this->pivot !== null,
                fn() => [
                    'pivot_id'              => $this->pivot->id ?? null,
                    'campaign_price'        => (float) ($this->pivot->campaign_price ?? 0),
                    'bottom_price'          => (float) ($this->pivot->bottom_price ?? 0),
                    'normal_price_snapshot' => (float) ($this->pivot->normal_price_snapshot ?? 0),
                    'discount_price'        => (float) ($this->pivot->discount_price ?? 0),
                    'promotion_stock'       => (int) ($this->pivot->promotion_stock ?? 0),
                    'purchase_limit'        => (int) ($this->pivot->purchase_limit ?? 0),
                    'approval_status'       => $this->pivot->approval_status ?? 'Pending',
                    'notes'                 => $this->pivot->notes ?? null,
                ]
            ),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
