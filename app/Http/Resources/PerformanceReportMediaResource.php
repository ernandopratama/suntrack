<?php

namespace App\Http\Resources;

use App\Models\PerformanceReportMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PerformanceReportMedia */
class PerformanceReportMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $token = $request->route('token');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'url' => $token
                ? url("/api/v1/public/review/{$token}/media/{$this->id}")
                : url("/api/v1/admin/performance-reports/{$this->performance_report_id}/media/{$this->id}"),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
