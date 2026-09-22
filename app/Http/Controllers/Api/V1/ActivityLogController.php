<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\Authorization\DataScopeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request, DataScopeService $dataScope): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['sometimes', 'string', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $dataScope->scopeActivityLogs(ActivityLog::query(), $request->user());
        $availableActions = (clone $query)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (! empty($validated['search'])) {
            $search = '%'.$validated['search'].'%';
            $query->where(function ($activity) use ($search) {
                $activity->where('description', 'like', $search)
                    ->orWhere('action', 'like', $search)
                    ->orWhere('actor_name', 'like', $search)
                    ->orWhere('actor_type', 'like', $search)
                    ->orWhere('loggable_type', 'like', $search)
                    ->orWhere('loggable_id', 'like', $search);
            });
        }

        $logs = $query->latest('created_at')->paginate((int) ($validated['per_page'] ?? 20));

        return $this->success('Log aktivitas berhasil dimuat.', [
            'activity_logs' => ActivityLogResource::collection($logs)->response()->getData(true),
            'filter_options' => [
                'actions' => $availableActions,
            ],
        ]);
    }
}
