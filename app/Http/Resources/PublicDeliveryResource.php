<?php

namespace App\Http\Resources;

use App\Models\PerformanceReport;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task|PerformanceReport */
class PublicDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;
        if ($resource instanceof Task) {
            return [
                'id' => $resource->id,
                'type' => 'Task',
                'name' => $resource->name,
                'description' => $resource->description,
                'status' => $resource->progress_status,
                'priority' => $resource->priority,
                'deadline' => $resource->deadline?->toIso8601String(),
                'completion_summary' => $resource->completion_summary,
                'completion_details' => $resource->completion_details,
                'completed_at' => $resource->completed_at?->toIso8601String(),
                'brand' => $resource->brand ? ['id' => $resource->brand->id, 'name' => $resource->brand->name] : null,
                'pic' => $resource->pic ? ['name' => $resource->pic->name] : null,
                'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
                'comments' => CommentResource::collection($this->whenLoaded('comments')),
            ];
        }

        if (! $resource instanceof PerformanceReport) {
            return [];
        }

        return [
            'id' => $resource->id,
            'type' => 'PerformanceReport',
            'name' => $resource->title,
            'report_type' => $resource->report_type,
            'period_start' => $resource->period_start->format('Y-m-d'),
            'period_end' => $resource->period_end->format('Y-m-d'),
            'executive_summary' => $resource->executive_summary,
            'content' => $resource->content,
            'status' => $resource->status,
            'version' => $resource->version,
            'published_at' => $resource->published_at?->toIso8601String(),
            'brand' => $resource->brand ? ['id' => $resource->brand->id, 'name' => $resource->brand->name] : null,
            'pic' => $resource->pic ? ['name' => $resource->pic->name] : null,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
