<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecureLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'url' => url('/review/' . $this->token),
            'status' => $this->status, // Active, Expired, Revoked
            'expires_at' => $this->expires_at ? $this->expires_at->toIso8601String() : null,
            'revoked_at' => $this->revoked_at ? $this->revoked_at->toIso8601String() : null,
            'last_accessed_at' => $this->last_accessed_at ? $this->last_accessed_at->toIso8601String() : null,
            'view_count' => (int) $this->view_count,
            'created_by_name' => $this->creator ? $this->creator->name : 'System Admin',
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
