<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Exposes required frontend fields and automatically computed metrics.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasVariants = $this->relationLoaded('variants');

        $totalProducts = $hasVariants
            ? $this->variants->pluck('product_id')->unique()->count()
            : ($this->variants_count ?? 0);

        $totalVariants = $hasVariants
            ? $this->variants->count()
            : ($this->variants_count ?? 0);

        $totalPromotionStock = $hasVariants
            ? (int) $this->variants->sum('pivot.promotion_stock')
            : 0;

        $totalEstimatedValue = $hasVariants
            ? (float) $this->variants->sum(fn($v) => ($v->pivot->campaign_price ?? 0) * ($v->pivot->promotion_stock ?? 0))
            : 0.0;

        // Approved products placeholder for future approval workflow sprint
        $totalApprovedProducts = $hasVariants
            ? $this->variants->where('pivot.approval_status', 'Approved')->count()
            : 0;

        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'start_date'  => $this->start_date?->format('Y-m-d'),
            'end_date'    => $this->end_date?->format('Y-m-d'),

            // Eager loaded relationships
            'campaign'    => $this->whenLoaded('campaign', fn() => [
                'id'   => $this->campaign->id,
                'name' => $this->campaign->name,
            ]),
            'brand'       => $this->whenLoaded('brand', fn() => [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ]),

            // Automatically calculated metrics
            'total_products'                  => $totalProducts,
            'total_variants'                  => $totalVariants,
            'total_promotion_stock'           => $totalPromotionStock,
            'total_estimated_promotion_value' => $totalEstimatedValue,
            'total_approved_products'         => $totalApprovedProducts,

            'metrics' => [
                'total_products'                  => $totalProducts,
                'total_variants'                  => $totalVariants,
                'total_promotion_stock'           => $totalPromotionStock,
                'total_estimated_promotion_value' => $totalEstimatedValue,
                'total_approved_products'         => $totalApprovedProducts,
            ],

            'secure_link' => $this->whenLoaded('secureLinks', fn() => $this->secureLinks->first() ? new SecureLinkResource($this->secureLinks->first()) : null),
            'approval_histories' => ApprovalHistoryResource::collection($this->whenLoaded('approvalHistories')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),

            'updated_at'  => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at'  => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
