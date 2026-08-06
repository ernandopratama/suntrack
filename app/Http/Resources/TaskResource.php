<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'progress_status' => $this->progress_status,
            'requires_visual' => $this->requires_visual,
            'visual_type' => $this->visual_type,
            'creative_brief' => $this->creative_brief,
            'deadline' => $this->deadline?->format('Y-m-d H:i:s'),
            'visual_link' => $this->visual_link,
            'visual_file_path' => $this->visual_file_path,
            'visual_file_name' => $this->visual_file_name,
            'visual_file_url' => $this->visual_file_path ? Storage::disk('public')->url($this->visual_file_path) : null,
            'campaign_id' => $this->campaign_id,
            'campaign' => $this->whenLoaded('campaign', function () {
                return [
                    'id' => $this->campaign->id,
                    'name' => $this->campaign->name,
                    'brand' => $this->campaign->brand?->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
