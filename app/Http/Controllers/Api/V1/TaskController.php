<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Campaign;
use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TaskRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $campaignId = $request->get('campaign_id');
        $isSuperAdmin = $user->hasRole('Super Admin');

        $tasks = $this->repository->getFilteredPaginated(
            campaignId: $campaignId,
            filters: $request->only(['search', 'status']),
            perPage: (int) $request->get('per_page', 15)
        );

        // Filter by user's company (kecuali Super Admin)
        if (!$isSuperAdmin) {
            $tasks->getCollection()->transform(function ($task) use ($user) {
                if ($task->campaign->brand?->company_id !== $user->company_id) {
                    return null;
                }
                return $task;
            });

            $tasks->setCollection(
                $tasks->getCollection()->filter()->values()
            );
        }

        return $this->success('Tasks retrieved successfully.', [
            'tasks' => TaskResource::collection($tasks)->response()->getData(true)
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify campaign belongs to user's company
        $campaign = Campaign::with('brand')->findOrFail($data['campaign_id']);

        // Allow if user is Super Admin OR campaign brand matches user's company
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyMatch = $campaign->brand && $campaign->brand->company_id === $user->company_id;

        if (!$isSuperAdmin && !$companyMatch) {
            return $this->error('Unauthorized.', [], 403);
        }

        $task = Task::create($data);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Task '{$task->name}' was created for campaign '{$campaign->name}'.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $task,
            actorId: $user->id
        );

        return $this->success('Task created successfully.', [
            'task' => new TaskResource($task)
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $user = request()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyMatch = $task->campaign->brand && $task->campaign->brand->company_id === $user->company_id;

        if (!$isSuperAdmin && !$companyMatch) {
            return $this->error('Unauthorized.', [], 403);
        }

        $task->load('campaign.brand');

        return $this->success('Task retrieved successfully.', [
            'task' => new TaskResource($task)
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyMatch = $task->campaign->brand && $task->campaign->brand->company_id === $user->company_id;

        if (!$isSuperAdmin && !$companyMatch) {
            return $this->error('Unauthorized.', [], 403);
        }

        $oldStatus = $task->progress_status;
        $task->update($request->validated());

        if ($oldStatus !== $task->progress_status) {
            ActivityLogger::log(
                action: ActivityType::StatusChanged->value,
                description: "Task '{$task->name}' status changed from {$oldStatus} to {$task->progress_status}.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $task,
                actorId: $user->id,
                properties: ['old_status' => $oldStatus, 'new_status' => $task->progress_status]
            );
        } else {
            ActivityLogger::log(
                action: ActivityType::Updated->value,
                description: "Task '{$task->name}' was updated.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $task,
                actorId: $user->id
            );
        }

        return $this->success('Task updated successfully.', [
            'task' => new TaskResource($task->fresh()->load('campaign.brand'))
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $user = request()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyMatch = $task->campaign->brand && $task->campaign->brand->company_id === $user->company_id;

        if (!$isSuperAdmin && !$companyMatch) {
            return $this->error('Unauthorized.', [], 403);
        }

        $taskName = $task->name;
        $task->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Task '{$taskName}' was deleted.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $task,
            actorId: $user->id
        );

        return $this->success('Task deleted successfully.');
    }
}
