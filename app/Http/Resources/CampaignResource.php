<?php

namespace App\Http\Resources;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\Workflow\CampaignDeadlineService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Campaign */
class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $deadlineState = app(CampaignDeadlineService::class)->state($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'objective' => $this->objective,
            'description' => $this->description,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'deadline' => $this->deadline ? $this->deadline->format('Y-m-d H:i:s') : null,
            'deadline_state' => $deadlineState,
            'deadline_state_label' => match ($deadlineState) {
                'overdue' => 'Overdue',
                'approaching_deadline' => 'Approaching Deadline',
                default => null,
            },
            'status' => $this->status,
            'status_label' => CampaignStatus::tryFrom($this->status)?->label() ?? $this->status,
            'priority' => $this->priority,
            'brand_id' => $this->brand_id,
            'created_by' => $this->created_by,
            'pic_id' => $this->pic_id,
            'notes' => $this->notes,
            'approval_notes' => $this->approval_notes,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),

            // Eager loaded relationships
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null),
            'pic' => $this->whenLoaded('pic', function () {
                return $this->pic ? [
                    'id' => $this->pic->id,
                    'name' => $this->pic->name,
                ] : null;
            }),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ])->values()),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
