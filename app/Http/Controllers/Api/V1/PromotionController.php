<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Http\Resources\PromotionResource;
use App\Models\ApprovalHistory;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Promotion;
use App\Repositories\PromotionRepository;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PromotionRepository $repository,
        protected DataScopeService $dataScope
    ) {}

    /**
     * Display a paginated, filterable listing of promotions.
     * Supports: search (code/name), status filter, campaign filter.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Promotion::class);

        $user = $request->user();

        $promotions = $this->repository->getFilteredPaginated(
            scope: $user,
            filters: $request->only(['search', 'status', 'campaign_id']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Promotions retrieved successfully.', [
            'promotions' => PromotionResource::collection($promotions)->response()->getData(true),
        ]);
    }

    /**
     * Store a newly created promotion.
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $this->authorize('create', Promotion::class);

        $user = $request->user();
        $data = $request->validated();

        if (empty($data['brand_id'])) {
            $data['brand_id'] = $this->dataScope->scopeBrands(Brand::query(), $user)->value('id');
        }

        if (empty($data['brand_id']) || ! $this->dataScope->canAccessBrandId($user, $data['brand_id'])) {
            abort(404);
        }

        if (! empty($data['campaign_id'])) {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            if (! $this->dataScope->canAccess($user, $campaign) || $campaign->brand_id !== $data['brand_id']) {
                abort(404);
            }
        }

        $promotion = new Promotion;
        $promotion->fill($data);
        $promotion->save();
        $promotion->load(['campaign', 'brand']);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Promotion '{$promotion->code} - {$promotion->name}' was created.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id
        );

        return $this->success('Promotion created successfully.', [
            'promotion' => new PromotionResource($promotion),
        ], 201);
    }

    /**
     * Display the specified promotion with eager loaded relationships.
     */
    public function show(Promotion $promotion, Request $request): JsonResponse
    {
        $this->authorize('view', $promotion);

        $promotion->load(['campaign', 'brand', 'variants', 'comments', 'approvalHistories', 'secureLinks']);

        return $this->success('Promotion retrieved successfully.', [
            'promotion' => new PromotionResource($promotion),
        ]);
    }

    /**
     * Update the specified promotion.
     * Logs status changes and campaign link/unlink separately.
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        $user = $request->user();
        $oldStatus = $promotion->status;
        $oldCampaignId = $promotion->campaign_id;

        if ($request->filled('campaign_id')) {
            $campaign = Campaign::findOrFail($request->input('campaign_id'));
            if (! $this->dataScope->canAccess($user, $campaign) || $campaign->brand_id !== $promotion->brand_id) {
                abort(404);
            }
        }

        $promotion->update($request->validated());

        // Log status change if applicable
        if ($oldStatus !== $promotion->status) {
            ActivityLogger::log(
                action: ActivityType::StatusChanged->value,
                description: "Promotion '{$promotion->code}' status changed from {$oldStatus} to {$promotion->status}.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $promotion,
                actorId: $user->id,
                properties: ['old_status' => $oldStatus, 'new_status' => $promotion->status]
            );
        }

        // Log campaign link/unlink change
        if ($oldCampaignId !== $promotion->campaign_id) {
            $action = $promotion->campaign_id
                ? ActivityType::Created->value.' (Linked to Campaign)'
                : ActivityType::Updated->value.' (Unlinked from Campaign)';

            ActivityLogger::log(
                action: $action,
                description: $promotion->campaign_id
                    ? "Promotion '{$promotion->code}' was linked to a Campaign."
                    : "Promotion '{$promotion->code}' was unlinked from its Campaign.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $promotion,
                actorId: $user->id,
                properties: ['old_campaign_id' => $oldCampaignId, 'new_campaign_id' => $promotion->campaign_id]
            );
        } else {
            ActivityLogger::log(
                action: ActivityType::Updated->value,
                description: "Promotion '{$promotion->code} - {$promotion->name}' was updated.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $promotion,
                actorId: $user->id
            );
        }

        $promotion->load(['campaign', 'brand']);

        return $this->success('Promotion updated successfully.', [
            'promotion' => new PromotionResource($promotion),
        ]);
    }

    /**
     * Execute Batch Approval/Rejection on selected or all variants (Sprint 8).
     */
    public function batchApproval(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);
        abort_unless($request->user()->can('promotion.approve'), 403);

        $request->validate([
            'action' => ['required', Rule::in(['approve_selected', 'reject_selected', 'approve_all', 'reject_all'])],
            'variant_ids' => ['required_if:action,approve_selected,reject_selected', 'array'],
            'variant_ids.*' => ['uuid'],
            'rejection_notes' => ['required_if:action,reject_selected,reject_all', 'nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $action = $request->action;
        $targetStatus = str_starts_with($action, 'approve') ? 'Approved' : 'Rejected';
        $notes = $targetStatus === 'Rejected' ? $request->rejection_notes : null;

        $variantsQuery = $promotion->variants();
        if (in_array($action, ['approve_selected', 'reject_selected'])) {
            $variantsQuery->whereIn('variants.id', $request->variant_ids);
        }
        $targetVariants = $variantsQuery->get();

        if ($targetVariants->isEmpty()) {
            return $this->error('No matching variants found for batch operation.', [], 404);
        }

        $updatedCount = 0;
        foreach ($targetVariants as $variant) {
            $oldStatus = $variant->pivot->approval_status ?? 'Pending';

            // Update pivot
            $promotion->variants()->updateExistingPivot($variant->id, [
                'approval_status' => $targetStatus,
                'rejection_notes' => $notes ?: ($variant->pivot->rejection_notes ?? null),
            ]);

            // Create immutable approval history
            ApprovalHistory::create([
                'promotion_id' => $promotion->id,
                'variant_id' => $variant->id,
                'reviewer_name' => $user->name.' (Admin)',
                'reviewer_position' => 'Internal Admin',
                'old_status' => $oldStatus,
                'new_status' => $targetStatus,
                'notes' => $notes,
            ]);

            $updatedCount++;
        }

        // Dynamic status recalculation
        $promotion->recalculateApprovalStatus($user->name.' (Admin)', 'Internal Admin');

        ActivityLogger::log(
            action: 'Batch Approval Executed',
            description: "Admin {$user->name} executed [{$action}]: marked {$updatedCount} variants as {$targetStatus}.".($notes ? " (Note: {$notes})" : ''),
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id,
            properties: [
                'action' => $action,
                'target_status' => $targetStatus,
                'updated_count' => $updatedCount,
                'rejection_notes' => $notes,
            ]
        );

        $promotion->load(['campaign', 'brand', 'variants', 'comments', 'approvalHistories', 'secureLinks']);

        return $this->success("Batch approval [{$action}] berhasil diproses untuk {$updatedCount} varian.", [
            'promotion' => new PromotionResource($promotion),
        ]);
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        $this->authorize('delete', $promotion);

        $user = request()->user();
        $promotionName = "{$promotion->code} - {$promotion->name}";
        $promotion->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Promotion '{$promotionName}' was deleted.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id
        );

        return $this->success('Promotion deleted successfully.');
    }
}
