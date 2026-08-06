<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'description' => $this->description,
            'current_price' => $this->current_price,
            'status'      => $this->status,
            'brand'       => $this->whenLoaded('brand', fn() => [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'variants_count' => $this->variants_count ?? 0,
            'variants'    => VariantResource::collection($this->whenLoaded('variants')),
            'updated_at'  => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at'  => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
