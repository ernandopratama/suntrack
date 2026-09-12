<?php

namespace App\Http\Resources;

use App\Enums\PerformanceReportStatus;
use App\Models\PerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PerformanceReport */
class PerformanceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'created_by' => $this->created_by,
            'author_id' => $this->author_id,
            'pic_id' => $this->pic_id,
            'supersedes_report_id' => $this->supersedes_report_id,
            'report_type' => $this->report_type,
            'title' => $this->title,
            'period_start' => $this->period_start->format('Y-m-d'),
            'period_end' => $this->period_end->format('Y-m-d'),
            'turnover' => (float) $this->turnover,
            'order_count' => (int) $this->order_count,
            'ad_spend' => (float) $this->ad_spend,
            'ad_sales' => (float) $this->ad_sales,
            'roas' => $this->ad_spend > 0 ? round((float) $this->ad_sales / (float) $this->ad_spend, 2) : null,
            'acos' => $this->ad_sales > 0 ? round((float) $this->ad_spend / (float) $this->ad_sales * 100, 2) : null,
            'ad_contribution' => $this->turnover > 0 ? round((float) $this->ad_sales / (float) $this->turnover * 100, 2) : null,
            'average_order_value' => $this->order_count > 0 ? round((float) $this->turnover / $this->order_count, 2) : null,
            'executive_summary' => $this->executive_summary,
            'content' => $this->content,
            'findings' => $this->findings,
            'action_plan' => $this->action_plan,
            'status' => $this->status,
            'status_label' => PerformanceReportStatus::tryFrom($this->status)?->label() ?? $this->status,
            'version' => $this->version,
            'review_notes' => $this->review_notes,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null),
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null),
            'pic' => $this->whenLoaded('pic', fn () => $this->pic ? [
                'id' => $this->pic->id,
                'name' => $this->pic->name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'media' => PerformanceReportMediaResource::collection($this->whenLoaded('media')),
            'secure_link' => $this->whenLoaded('secureLink', fn () => $this->secureLink
                ? new SecureLinkResource($this->secureLink)
                : null),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
