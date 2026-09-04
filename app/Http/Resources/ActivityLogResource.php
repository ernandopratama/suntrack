<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,
            'actor_type' => $this->actor_type,
            'actor_name' => $this->actor_name,
            'actor_position' => $this->actor_position,
            'target_type' => class_basename($this->loggable_type ?? ''),
            'target_id' => $this->loggable_id,
            'properties' => $this->properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
