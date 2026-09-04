<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'author_position' => $this->author_position,
            'author_type' => $this->author_type ?? 'Brand', // Admin or Brand
            'body' => $this->body,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
