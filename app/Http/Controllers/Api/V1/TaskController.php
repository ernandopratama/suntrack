<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\WorkflowTransitionRequest;
use App\Http\Resources\TaskResource;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\NotificationLog;
use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Services\Workflow\RecurringTaskService;
use App\Services\Workflow\TaskReminderService;
use App\Services\Workflow\WorkflowAssignmentService;
use App\Services\Workflow\WorkflowTransitionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TaskRepository $repository,
        protected DataScopeService $dataScope,
        protected WorkflowAssignmentService $assignments,
        protected WorkflowTransitionService $transitions,
        protected TaskReminderService $reminders,
        protected RecurringTaskService $recurringTasks
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $campaignId = $request->get('campaign_id');

        $tasks = $this->repository->getFilteredPaginated(
            scope: $user,
            campaignId: $campaignId,
            filters: $request->only(['search', 'status', 'priority']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Data task berhasil dimuat.', [
            'tasks' => TaskResource::collection($tasks)->response()->getData(true),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $notifications = NotificationLog::query()
            ->where('type', 'in_app')
            ->where('recipient', $request->user()->id)
            ->where('notifiable_type', Task::class)
            ->whereIn('status', ['sent', 'delivered'])
            ->whereNull('read_at')
            ->latest()
            ->limit(50)
            ->get(['id', 'subject', 'body', 'notifiable_id', 'created_at'])
            ->unique('notifiable_id')
            ->take(10)
            ->values();

        return $this->success('Pemberitahuan task berhasil dimuat.', [
            'notifications' => $notifications,
        ]);
    }

    public function readNotifications(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $data = $request->validate([
            'notification_ids' => ['sometimes', 'array', 'min:1'],
            'notification_ids.*' => ['uuid', 'distinct'],
        ]);

        $query = NotificationLog::query()
            ->where('type', 'in_app')
            ->where('recipient', $request->user()->id)
            ->where('notifiable_type', Task::class)
            ->whereNull('read_at');

        if (! empty($data['notification_ids'])) {
            $query->whereIn('id', $data['notification_ids']);
        }

        $query->update(['read_at' => now()]);

        return $this->success('Pemberitahuan task telah ditandai sebagai dibaca.');
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $user = $request->user();
        $data = $request->validated();

        $isPersonal = (bool) ($data['is_personal'] ?? false);
        $brand = null;

        if ($isPersonal) {
            $data['brand_id'] = null;
            $data['campaign_id'] = null;
            $data['pic_id'] = $user->id;
            $data['assignee_id'] = $user->id;
            $data['progress_status'] = 'assigned';
        } else {
            $brand = Brand::findOrFail($data['brand_id']);
            if (! $this->dataScope->canAccess($user, $brand)) {
                abort(404);
            }

            if (! empty($data['campaign_id'])) {
                $campaign = Campaign::findOrFail($data['campaign_id']);
                if (! $this->dataScope->canAccess($user, $campaign)) {
                    abort(404);
                }
                if ($campaign->brand_id !== $brand->id) {
                    return $this->error('Kampanye harus berasal dari brand yang dipilih.', ['campaign_id' => ['Brand kampanye tidak sesuai.']], 422);
                }
            }

            if (array_key_exists('pic_id', $data) || array_key_exists('assignee_id', $data)) {
                $this->assignments->assertManager($user);
                $this->assignments->assertPic($data['pic_id'] ?? null);
                if (! empty($data['assignee_id'])) {
                    $this->assignments->assertTeamMember($data['assignee_id'], $brand->id);
                }
            }
        }

        $data['created_by'] = $user->id;
        $task = new Task;
        $task->forceFill($data)->save();
        $this->recurringTasks->schedule($task);
        $this->reminders->schedule($task);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: $task->is_personal
                ? "Task pribadi '{$task->name}' dibuat."
                : "Task '{$task->name}' dibuat untuk Brand '{$brand->name}'.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $task,
            actorId: $user->id,
            properties: [
                'brand_id' => $task->brand_id,
                'campaign_id' => $task->campaign_id,
                'pic_id' => $task->pic_id,
                'assignee_id' => $task->assignee_id,
            ]
        );

        return $this->success('Task berhasil dibuat.', [
            'task' => new TaskResource($task->load(['brand', 'campaign', 'pic', 'assignee', 'creator'])),
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['brand', 'campaign', 'pic', 'assignee', 'creator']);

        return $this->success('Detail task berhasil dimuat.', [
            'task' => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $user = $request->user();

        $data = $request->validated();
        $targetStatus = $data['progress_status'] ?? null;
        unset($data['progress_status']);
        $transitionNote = $data['transition_note'] ?? null;
        unset($data['transition_note']);
        $recurrenceChanged = collect([
            'deadline',
            'recurrence_type',
            'recurrence_interval',
            'recurrence_time',
            'recurrence_weekdays',
            'recurrence_month_day',
            'recurrence_ends_at',
            'recurrence_max_occurrences',
        ])
            ->contains(fn (string $field) => array_key_exists($field, $data));

        if ($task->is_personal) {
            unset($data['brand_id'], $data['campaign_id'], $data['pic_id'], $data['assignee_id']);
            $brandId = null;
        } else {
            $brandId = $data['brand_id'] ?? $task->brand_id;

            if (! $this->dataScope->canAccessBrandId($user, $brandId)) {
                abort(404);
            }

            if (! empty($data['campaign_id'])) {
                $campaign = Campaign::findOrFail($data['campaign_id']);
                if ($campaign->brand_id !== $brandId || ! $this->dataScope->canAccess($user, $campaign)) {
                    return $this->error('Kampanye harus berasal dari brand yang dipilih.', ['campaign_id' => ['Brand kampanye tidak sesuai atau tidak dapat diakses.']], 422);
                }
            }

            if (array_key_exists('pic_id', $data) || array_key_exists('assignee_id', $data) || $brandId !== $task->brand_id) {
                $this->assignments->assertManager($user);
                $this->assignments->assertPic($data['pic_id'] ?? $task->pic_id);
                $assigneeId = $data['assignee_id'] ?? $task->assignee_id;
                if ($assigneeId !== null) {
                    $this->assignments->assertTeamMember($assigneeId, $brandId);
                }
            }
        }

        $oldOwnership = [
            'brand_id' => $task->brand_id,
            'campaign_id' => $task->campaign_id,
            'pic_id' => $task->pic_id,
            'assignee_id' => $task->assignee_id,
        ];
        $task = DB::transaction(function () use ($task, $data, $targetStatus, $transitionNote, $oldOwnership, $user, $recurrenceChanged): Task {
            $task->update($data);
            if ($recurrenceChanged) {
                $this->recurringTasks->schedule($task);
            }
            if ($recurrenceChanged || array_key_exists('priority', $data)) {
                $this->reminders->schedule($task, true);
            }
            $newOwnership = [
                'brand_id' => $task->brand_id,
                'campaign_id' => $task->campaign_id,
                'pic_id' => $task->pic_id,
                'assignee_id' => $task->assignee_id,
            ];

            if ($targetStatus !== null && $targetStatus !== $task->progress_status) {
                if ($oldOwnership !== $newOwnership) {
                    ActivityLogger::log(
                        action: 'Assignment Changed',
                        description: 'Kepemilikan task diperbarui.',
                        actorType: 'Admin',
                        actorName: $user->name,
                        loggable: $task,
                        actorId: $user->id,
                        properties: ['old_ownership' => $oldOwnership, 'new_ownership' => $newOwnership]
                    );
                }

                return $this->transitions->task($task, $user, $targetStatus, $transitionNote);
            }

            ActivityLogger::log(
                action: ActivityType::Updated->value,
                description: "Task '{$task->name}' diperbarui.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $task,
                actorId: $user->id,
                properties: $oldOwnership === $newOwnership ? null : [
                    'old_ownership' => $oldOwnership,
                    'new_ownership' => $newOwnership,
                ]
            );

            return $task->refresh();
        });

        return $this->success('Task berhasil diperbarui.', [
            'task' => new TaskResource($task->load(['brand', 'campaign', 'pic', 'assignee', 'creator'])),
        ]);
    }

    public function transition(WorkflowTransitionRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->transitions->task(
            $task,
            $request->user(),
            $request->validated('status'),
            $request->validated('note')
        );

        return $this->success('Status task berhasil diperbarui.', [
            'task' => new TaskResource($task->load(['brand', 'campaign', 'pic', 'assignee', 'creator'])),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $user = request()->user();

        $taskName = $task->name;
        $task->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Task '{$taskName}' dihapus.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $task,
            actorId: $user->id
        );

        return $this->success('Task berhasil dihapus.');
    }
}
