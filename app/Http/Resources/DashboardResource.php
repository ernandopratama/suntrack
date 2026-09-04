<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property-read array<string, mixed> $kpi
 * @property-read array<string, mixed> $deadlines
 * @property-read Collection<int, ActivityLog> $recent_activities
 * @property-read string $server_time
 */
class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Concise payload tailored specifically for the Operational Command Center (Refinement #9).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kpi' => $this->kpi,
            'deadlines' => $this->deadlines,
            'recent_activities' => $this->formatRecentActivities($this->recent_activities),
            'server_time' => $this->server_time,
        ];
    }

    /**
     * Format recent activities into a lightweight structure for timeline rendering.
     */
    protected function formatRecentActivities(Collection $activities): Collection
    {
        return $activities->map(function (ActivityLog $log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'actor_type' => $log->actor_type,
                'actor_name' => $log->actor_name ?? 'System',
                'actor_position' => $log->actor_position,
                'target_type' => class_basename($log->loggable_type ?? ''),
                'target_id' => $log->loggable_id,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'time_ago' => $log->created_at->diffForHumans(),
            ];
        });
    }
}
