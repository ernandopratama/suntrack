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

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (! empty($validated['search'])) {
            $query->where('description', 'like', '%'.$validated['search'].'%');
        }

        $logs = $query->latest('created_at')->paginate((int) ($validated['per_page'] ?? 20));

        return $this->success('Activity logs retrieved successfully.', [
            'activity_logs' => ActivityLogResource::collection($logs)->response()->getData(true),
        ]);
    }
}
