<?php

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'progress_status' => $this->progress_status,
            'status' => $this->progress_status,
            'status_label' => TaskStatus::tryFrom($this->progress_status)?->label() ?? $this->progress_status,
            'priority' => $this->priority,
            'requires_visual' => $this->requires_visual,
            'visual_type' => $this->visual_type,
            'creative_brief' => $this->creative_brief,
            'deadline' => $this->deadline?->format('Y-m-d H:i:s'),
            'visual_link' => $this->visual_link,
            'visual_file_path' => $this->visual_file_path,
            'visual_file_name' => $this->visual_file_name,
            'visual_file_url' => $this->visual_file_path ? Storage::disk('public')->url($this->visual_file_path) : null,
            'campaign_id' => $this->campaign_id,
            'brand_id' => $this->brand_id,
            'created_by' => $this->created_by,
            'pic_id' => $this->pic_id,
            'assignee_id' => $this->assignee_id,
            'notes' => $this->notes,
            'hold_reason' => $this->hold_reason,
            'revision_notes' => $this->revision_notes,
            'completion_summary' => $this->completion_summary,
            'completion_details' => $this->completion_details,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'next_reminder_at' => $this->next_reminder_at?->format('Y-m-d H:i:s'),
            'last_reminded_at' => $this->last_reminded_at?->format('Y-m-d H:i:s'),
            'reminder_count' => $this->reminder_count,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null),
            'pic' => $this->whenLoaded('pic', fn () => $this->pic ? [
                'id' => $this->pic->id,
                'name' => $this->pic->name,
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'campaign' => $this->whenLoaded('campaign', function () {
                return $this->campaign ? [
                    'id' => $this->campaign->id,
                    'name' => $this->campaign->name,
                    'brand' => $this->campaign->brand?->name,
                ] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
