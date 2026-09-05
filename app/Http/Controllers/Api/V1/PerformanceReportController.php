<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Enums\PerformanceReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerformanceReportRequest;
use App\Http\Requests\UpdatePerformanceReportRequest;
use App\Http\Requests\WorkflowTransitionRequest;
use App\Http\Resources\PerformanceReportResource;
use App\Models\Brand;
use App\Models\PerformanceReport;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Services\Workflow\WorkflowAssignmentService;
use App\Services\Workflow\WorkflowTransitionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private DataScopeService $dataScope,
        private WorkflowAssignmentService $assignments,
        private WorkflowTransitionService $transitions
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReport::class);

        $query = $this->dataScope->scopePerformanceReports(
            PerformanceReport::query()->with(['brand', 'author', 'pic', 'creator']),
            $request->user()
        );

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('title', 'like', "%{$search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('report_type')) {
            $query->where('report_type', $request->string('report_type')->toString());
        }

        $reports = $query->latest()->paginate((int) $request->integer('per_page', 15));

        return $this->success('Performance reports retrieved successfully.', [
            'reports' => PerformanceReportResource::collection($reports)->response()->getData(true),
        ]);
    }

    public function store(StorePerformanceReportRequest $request): JsonResponse
    {
        $this->authorize('create', PerformanceReport::class);
        $user = $request->user();
        $data = $request->validated();
        $brand = Brand::findOrFail($data['brand_id']);

        if (! $this->dataScope->canAccess($user, $brand)) {
            abort(404);
        }

        $this->assignments->assertPic($data['pic_id']);

        $data['created_by'] = $user->id;
        $data['author_id'] = $user->id;
        $data['status'] = PerformanceReportStatus::Draft->value;
        $report = new PerformanceReport;
        $report->forceFill($data)->save();

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Performance Report '{$report->title}' was created.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $report,
            actorId: $user->id,
            properties: [
                'brand_id' => $report->brand_id,
                'author_id' => $report->author_id,
                'pic_id' => $report->pic_id,
            ]
        );

        return $this->success('Performance report created successfully.', [
            'report' => new PerformanceReportResource($report->load(['brand', 'author', 'pic', 'creator'])),
        ], 201);
    }

    public function show(PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('view', $performanceReport);

        return $this->success('Performance report retrieved successfully.', [
            'report' => new PerformanceReportResource($performanceReport->load(['brand', 'author', 'pic', 'creator'])),
        ]);
    }

    public function update(UpdatePerformanceReportRequest $request, PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('update', $performanceReport);

        if ($performanceReport->status === PerformanceReportStatus::Published->value) {
            throw ValidationException::withMessages(['status' => 'Published reports are read-only. Create a new version instead.']);
        }

        $user = $request->user();
        $data = $request->validated();
        $brandId = $data['brand_id'] ?? $performanceReport->brand_id;

        if (! $this->dataScope->canAccessBrandId($user, $brandId)) {
            abort(404);
        }

        if ($brandId !== $performanceReport->brand_id
            || ($data['pic_id'] ?? $performanceReport->pic_id) !== $performanceReport->pic_id) {
            $this->assignments->assertManager($user);
        }
        $this->assignments->assertPic($data['pic_id'] ?? $performanceReport->pic_id);

        $oldOwnership = [
            'brand_id' => $performanceReport->brand_id,
            'author_id' => $performanceReport->author_id,
            'pic_id' => $performanceReport->pic_id,
        ];
        $performanceReport->update($data);
        $newOwnership = [
            'brand_id' => $performanceReport->brand_id,
            'author_id' => $performanceReport->author_id,
            'pic_id' => $performanceReport->pic_id,
        ];

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Performance Report '{$performanceReport->title}' was updated.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $performanceReport,
            actorId: $user->id,
            properties: $oldOwnership === $newOwnership ? null : [
                'old_ownership' => $oldOwnership,
                'new_ownership' => $newOwnership,
            ]
        );

        return $this->success('Performance report updated successfully.', [
            'report' => new PerformanceReportResource($performanceReport->load(['brand', 'author', 'pic', 'creator'])),
        ]);
    }

    public function destroy(PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('delete', $performanceReport);

        if ($performanceReport->status === PerformanceReportStatus::Published->value) {
            throw ValidationException::withMessages(['status' => 'Published reports cannot be deleted.']);
        }

        $performanceReport->delete();

        return $this->success('Performance report deleted successfully.');
    }

    public function transition(WorkflowTransitionRequest $request, PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('update', $performanceReport);

        $performanceReport = $this->transitions->report(
            $performanceReport,
            $request->user(),
            $request->validated('status'),
            $request->validated('note')
        );

        return $this->success('Performance report status updated successfully.', [
            'report' => new PerformanceReportResource($performanceReport->load(['brand', 'author', 'pic', 'creator'])),
        ]);
    }

    public function createVersion(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('update', $performanceReport);

        if ($performanceReport->status !== PerformanceReportStatus::Published->value) {
            throw ValidationException::withMessages(['status' => 'Only a published report can be versioned.']);
        }

        $user = $request->user();
        $version = DB::transaction(function () use ($performanceReport, $user): PerformanceReport {
            $performanceReport = PerformanceReport::query()
                ->whereKey($performanceReport->id)
                ->lockForUpdate()
                ->firstOrFail();
            $copy = $performanceReport->replicate([
                'status', 'version', 'review_notes', 'approved_at', 'published_at', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $copy->created_by = $user->id;
            $copy->author_id = $user->id;
            $copy->supersedes_report_id = $performanceReport->id;
            $copy->status = PerformanceReportStatus::Draft->value;
            $copy->version = $performanceReport->version + 1;
            $copy->save();

            ActivityLogger::log(
                action: 'Version Created',
                description: "Version {$copy->version} created from Performance Report '{$performanceReport->title}'.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $copy,
                actorId: $user->id,
                properties: ['supersedes_report_id' => $performanceReport->id]
            );

            return $copy;
        });

        return $this->success('Performance report version created successfully.', [
            'report' => new PerformanceReportResource($version->load(['brand', 'author', 'pic', 'creator'])),
        ], 201);
    }
}
