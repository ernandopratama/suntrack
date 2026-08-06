<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CommentResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'deadline' => $this->deadline ? $this->deadline->format('Y-m-d H:i:s') : null,
            'status' => $this->status,
            'company_id' => $this->company_id,
            'pic_id' => $this->pic_id,

            // Eager loaded relationships
            'pic' => $this->whenLoaded('pic', function () {
                return [
                    'id' => $this->pic->id,
                    'name' => $this->pic->name,
                ];
            }),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
