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
use App\Services\Reporting\PerformanceReportPublishingService;
use App\Services\Workflow\WorkflowTransitionService;
use App\Support\PerformanceReportRules;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private DataScopeService $dataScope,
        private WorkflowTransitionService $transitions,
        private PerformanceReportPublishingService $publishing
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReport::class);

        $query = $this->dataScope->scopePerformanceReports(
            PerformanceReport::query()->with(['brand', 'author', 'pic', 'creator', 'secureLink.creator']),
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
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->string('brand_id')->toString());
        }

        $reports = $query->latest()->paginate((int) $request->integer('per_page', 15));

        return $this->success('Performance reports retrieved successfully.', [
            'reports' => PerformanceReportResource::collection($reports)->response()->getData(true),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReport::class);
        /** @var Collection<int, Brand> $brands */
        $brands = $this->dataScope->scopeBrands(Brand::query(), $request->user())
            ->orderBy('name')
            ->get(['id', 'name']);
        $brandOptions = $brands->map(fn (Brand $brand) => [
            'id' => $brand->id,
            'name' => $brand->name,
            'report_types' => PerformanceReportRules::typesForBrand($brand->name),
        ]);

        return $this->success('Pilihan laporan berhasil dimuat.', [
            'brands' => $brandOptions,
            'report_types' => ['daily', 'weekly', 'monthly'],
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

        $data['created_by'] = $user->id;
        $data['author_id'] = $user->id;
        $data['pic_id'] = $user->id;
        $data['status'] = PerformanceReportStatus::Draft->value;
        $report = DB::transaction(function () use ($data, $user): PerformanceReport {
            $report = new PerformanceReport;
            $report->forceFill($data)->save();
            $this->publishing->ensureDraftLink($report, $user);

            return $report;
        });

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
            'report' => new PerformanceReportResource($this->loadReport($report)),
        ], 201);
    }

    public function show(PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('view', $performanceReport);

        return $this->success('Performance report retrieved successfully.', [
            'report' => new PerformanceReportResource($this->loadReport($performanceReport)),
        ]);
    }

    public function update(UpdatePerformanceReportRequest $request, PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('update', $performanceReport);

        $user = $request->user();
        $data = $request->validated();
        $brandId = $data['brand_id'] ?? $performanceReport->brand_id;

        if (! $this->dataScope->canAccessBrandId($user, $brandId)) {
            abort(404);
        }

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
            'report' => new PerformanceReportResource($this->loadReport($performanceReport)),
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
        $this->authorize('view', $performanceReport);

        $performanceReport = $this->transitions->report(
            $performanceReport,
            $request->user(),
            $request->validated('status'),
            $request->validated('note')
        );
        if ($performanceReport->status === PerformanceReportStatus::Published->value) {
            $this->publishing->activateLink($performanceReport, $request->user());
        }

        return $this->success('Performance report status updated successfully.', [
            'report' => new PerformanceReportResource($this->loadReport($performanceReport)),
        ]);
    }

    public function publish(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('update', $performanceReport);
        $report = $this->publishing->publish($performanceReport, $request->user());

        ActivityLogger::log(
            action: 'Published',
            description: "Performance Report '{$report->title}' was published.",
            actorType: $request->user()->hasRole('Tim') ? 'Tim' : 'Admin',
            actorName: $request->user()->name,
            loggable: $report,
            actorId: $request->user()->id,
            properties: ['published_at' => $report->published_at?->toIso8601String()]
        );

        return $this->success('Laporan berhasil dipublikasikan dan Secure Link telah aktif.', [
            'report' => new PerformanceReportResource($this->loadReport($report)),
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
            $this->publishing->ensureDraftLink($copy, $user);

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
            'report' => new PerformanceReportResource($this->loadReport($version)),
        ], 201);
    }

    private function loadReport(PerformanceReport $report): PerformanceReport
    {
        return $report->load(['brand', 'author', 'pic', 'creator', 'media.uploader', 'secureLink.creator']);
    }
}
