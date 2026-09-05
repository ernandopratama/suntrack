<?php

namespace App\Http\Resources;

use App\Models\SecureLinkAccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SecureLinkAccessLog */
class SecureLinkAccessLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accessed_at' => $this->accessed_at->toIso8601String(),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'referer' => $this->referer,
        ];
    }
}
