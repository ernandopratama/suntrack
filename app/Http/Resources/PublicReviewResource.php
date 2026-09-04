<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\Promotion;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Campaign
 * @mixin Promotion
 */
class PublicReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isPromotion = $this->resource instanceof Promotion;

        $variants = $isPromotion ? $this->variants : $this->promotions->load('variants')->pluck('variants')->flatten();
        $totalVariants = $variants->count();
        $approved = $variants->where('pivot.approval_status', 'Approved')->count();
        $rejected = $variants->where('pivot.approval_status', 'Rejected')->count();
        $pending = $variants->where('pivot.approval_status', 'Pending')->count();
        $completionPercentage = $totalVariants > 0 ? round(($approved + $rejected) / $totalVariants * 100, 1) : 0;

        $lastHistory = $isPromotion ? $this->approvalHistories()->first() : null;

        $variantsData = $variants->map(fn (Variant $variant) => $this->formatVariant($variant, true));

        $timeline = $this->activityLogs()->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'actor_type' => $log->actor_type,
                'actor_name' => $log->actor_name,
                'actor_position' => $log->actor_position,
                'properties' => $log->properties,
                'created_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
            ];
        });

        // For Campaign: collect promotions with their variants and product info
        $promotionsData = ! $isPromotion ? $this->promotions->map(function (Promotion $promo) {
            return [
                'id' => $promo->id,
                'code' => $promo->code,
                'name' => $promo->name,
                'description' => $promo->description,
                'start_date' => $promo->start_date ? $promo->start_date->toIso8601String() : null,
                'end_date' => $promo->end_date ? $promo->end_date->toIso8601String() : null,
                'status' => $promo->status,
                'variants' => $promo->variants->map(fn (Variant $variant) => $this->formatVariant($variant, false)),
            ];
        }) : collect([]);

        return [
            'id' => $this->id,
            'type' => $isPromotion ? 'Promotion' : 'Campaign',
            'code' => $isPromotion ? $this->code : null,
            'name' => $this->name,
            'description' => $this->description ?? $this->notes ?? null,
            'start_date' => $this->start_date ? $this->start_date->toIso8601String() : null,
            'end_date' => $this->end_date ? $this->end_date->toIso8601String() : null,
            'status' => $this->status,
            'brand' => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null,
            'campaign' => ($isPromotion && $this->campaign) ? [
                'id' => $this->campaign->id,
                'name' => $this->campaign->name,
                'start_date' => $this->campaign->start_date ? $this->campaign->start_date->toIso8601String() : null,
                'end_date' => $this->campaign->end_date ? $this->campaign->end_date->toIso8601String() : null,
            ] : null,
            'approval_summary' => [
                'total_variants' => $totalVariants,
                'approved' => $approved,
                'pending' => $pending,
                'rejected' => $rejected,
                'completion_percentage' => $completionPercentage,
                'last_updated' => $lastHistory ? $lastHistory->created_at->toIso8601String() : ($this->updated_at ? $this->updated_at->toIso8601String() : null),
                'last_reviewer' => $lastHistory ? [
                    'name' => $lastHistory->reviewer_name,
                    'position' => $lastHistory->reviewer_position,
                    'company_name' => $lastHistory->company_name,
                ] : null,
            ],
            'variants' => $variantsData,
            'promotions' => $promotionsData,
            'tasks' => ! $isPromotion ? $this->tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'progress_status' => $task->progress_status,
                    'requires_visual' => $task->requires_visual,
                    'visual_type' => $task->visual_type,
                    'creative_brief' => $task->creative_brief,
                    'deadline' => $task->deadline ? $task->deadline->toIso8601String() : null,
                    'visual_link' => $task->visual_link,
                    'visual_file_path' => $task->visual_file_path,
                    'visual_file_name' => $task->visual_file_name,
                    'visual_file_url' => $task->visual_file_path ? Storage::disk('public')->url($task->visual_file_path) : null,
                    'submitted_by' => $task->submitted_by,
                    'submitted_at' => $task->submitted_at ? $task->submitted_at->toIso8601String() : null,
                ];
            }) : collect([]),
            'approval_histories' => ApprovalHistoryResource::collection($isPromotion ? $this->approvalHistories()->get() : collect([])),
            'comments' => CommentResource::collection($this->comments()->get()),
            'timeline' => $timeline,
        ];
    }

    /** @return array<string, mixed> */
    private function formatVariant(Variant $variant, bool $includeNotes): array
    {
        $pivot = $variant->getRelationValue('pivot');
        $pivot = $pivot instanceof Pivot ? $pivot : null;

        $data = [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product ? $variant->product->name : 'Unknown Product',
            'name' => $variant->name,
            'sku' => $variant->sku,
            'normal_price_snapshot' => (float) $this->pivotValue($pivot, 'normal_price_snapshot', 0),
            'campaign_price' => (float) $this->pivotValue($pivot, 'campaign_price', 0),
            'discount_price' => (float) $this->pivotValue($pivot, 'discount_price', 0),
            'bottom_price' => (float) $this->pivotValue($pivot, 'bottom_price', 0),
            'promotion_stock' => (int) $this->pivotValue($pivot, 'promotion_stock', 0),
            'purchase_limit' => (int) $this->pivotValue($pivot, 'purchase_limit', 0),
            'approval_status' => $this->pivotValue($pivot, 'approval_status', 'Pending'),
            'rejection_notes' => $this->pivotValue($pivot, 'rejection_notes'),
        ];

        if ($includeNotes) {
            $data['notes'] = $this->pivotValue($pivot, 'notes');
        }

        return $data;
    }

    private function pivotValue(?Pivot $pivot, string $key, mixed $default = null): mixed
    {
        if ($pivot === null) {
            return $default;
        }

        return $pivot->getAttribute($key) ?? $default;
    }
}
