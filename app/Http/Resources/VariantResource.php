<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
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
            ]),
            // Pivot pricing data (available when loaded via promotion relationship)
            'promotion_pricing' => $this->when(
                $this->pivot !== null,
                fn() => [
                    'pivot_id'              => $this->pivot->id ?? null,
                    'campaign_price'        => $this->pivot->campaign_price ?? null,
                    'bottom_price'          => $this->pivot->bottom_price ?? null,
                    'normal_price_snapshot' => $this->pivot->normal_price_snapshot ?? null,
                    'discount_price'        => $this->pivot->discount_price ?? null,
                    'promotion_stock'       => $this->pivot->promotion_stock ?? null,
                    'purchase_limit'        => $this->pivot->purchase_limit ?? null,
                    'approval_status'       => $this->pivot->approval_status ?? null,
                    'notes'                 => $this->pivot->notes ?? null,
                ]
            ),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
