<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Requests\WorkflowTransitionRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Brand;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Services\Workflow\WorkflowAssignmentService;
use App\Services\Workflow\WorkflowTransitionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignRepository $repository,
        protected DataScopeService $dataScope,
        protected WorkflowAssignmentService $assignments,
        protected WorkflowTransitionService $transitions
    ) {}

    /**
     * Display a listing of the campaigns.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $user = $request->user();

        $campaigns = $this->repository->getFilteredPaginated(
            scope: $user,
            filters: $request->only(['search', 'status', 'priority']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Campaigns retrieved successfully.', [
            'campaigns' => CampaignResource::collection($campaigns)->response()->getData(true),
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $user = $request->user();

        $data = $request->validated();

        $brand = Brand::findOrFail($data['brand_id']);
        if (! $this->dataScope->canAccess($user, $brand)) {
            abort(404);
        }

        $memberIds = $data['member_ids'] ?? [];
        unset($data['member_ids']);

        if (array_key_exists('pic_id', $data) || $memberIds !== []) {
            $this->assignments->assertManager($user);
            $this->assignments->assertPic($data['pic_id'] ?? null);
            $this->assignments->assertTeamMembers($memberIds, $data['brand_id']);
        }

        $data['created_by'] = $user->id;
        $campaign = DB::transaction(function () use ($data, $memberIds, $user): Campaign {
            $campaign = new Campaign;
            $campaign->forceFill($data)->save();
            if ($memberIds !== []) {
                $campaign->members()->syncWithPivotValues($memberIds, ['assigned_by' => $user->id]);
            }

            return $campaign;
        });

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: 'Campaign "'.$campaign->name.'" was created.',
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $campaign,
            actorId: $user->id,
            properties: ['pic_id' => $campaign->pic_id, 'member_ids' => $memberIds]
        );

        return $this->success('Campaign created successfully.', [
            'campaign' => new CampaignResource($campaign->load(['pic', 'creator', 'members'])),
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $campaign->load(['brand', 'pic', 'creator', 'members', 'comments']);

        return $this->success('Campaign retrieved successfully.', [
            'campaign' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $user = $request->user();
        $data = $request->validated();
        $targetStatus = $data['status'] ?? null;
        unset($data['status']);
        $transitionNote = $data['transition_note'] ?? null;
        unset($data['transition_note']);
        $memberIdsProvided = array_key_exists('member_ids', $data);
        $memberIds = $data['member_ids'] ?? [];
        unset($data['member_ids']);
        $brandId = $data['brand_id'] ?? $campaign->brand_id;

        if (! $this->dataScope->canAccessBrandId($user, $brandId)) {
            abort(404);
        }

        if (array_key_exists('pic_id', $data) || $memberIdsProvided || $brandId !== $campaign->brand_id) {
            $this->assignments->assertManager($user);
            $this->assignments->assertPic($data['pic_id'] ?? $campaign->pic_id);
            $effectiveMembers = $memberIdsProvided
                ? $memberIds
                : $campaign->members()->pluck('users.id')->all();
            $this->assignments->assertTeamMembers($effectiveMembers, $brandId);
        }

        $oldOwnership = [
            'brand_id' => $campaign->brand_id,
            'pic_id' => $campaign->pic_id,
            'member_ids' => $campaign->members()->pluck('users.id')->sort()->values()->all(),
        ];
        $campaign = DB::transaction(function () use ($campaign, $data, $memberIdsProvided, $memberIds, $targetStatus, $transitionNote, $oldOwnership, $user): Campaign {
            $campaign->update($data);
            if ($memberIdsProvided) {
                $campaign->members()->syncWithPivotValues($memberIds, ['assigned_by' => $user->id]);
            }

            $newOwnership = [
                'brand_id' => $campaign->brand_id,
                'pic_id' => $campaign->pic_id,
                'member_ids' => $campaign->members()->pluck('users.id')->sort()->values()->all(),
            ];

            if ($targetStatus !== null && $targetStatus !== $campaign->status) {
                if ($oldOwnership !== $newOwnership) {
                    ActivityLogger::log(
                        action: 'Assignment Changed',
                        description: 'Campaign ownership was updated.',
                        actorType: 'Admin',
                        actorName: $user->name,
                        loggable: $campaign,
                        actorId: $user->id,
                        properties: ['old_ownership' => $oldOwnership, 'new_ownership' => $newOwnership]
                    );
                }

                return $this->transitions->campaign($campaign, $user, $targetStatus, $transitionNote);
            }

            ActivityLogger::log(
                action: ActivityType::Updated->value,
                description: 'Campaign "'.$campaign->name.'" was updated.',
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $campaign,
                actorId: $user->id,
                properties: $oldOwnership === $newOwnership ? null : [
                    'old_ownership' => $oldOwnership,
                    'new_ownership' => $newOwnership,
                ]
            );

            return $campaign->refresh();
        });

        return $this->success('Campaign updated successfully.', [
            'campaign' => new CampaignResource($campaign->load(['brand', 'pic', 'creator', 'members'])),
        ]);
    }

    public function transition(WorkflowTransitionRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign = $this->transitions->campaign(
            $campaign,
            $request->user(),
            $request->validated('status'),
            $request->validated('note')
        );

        return $this->success('Campaign status updated successfully.', [
            'campaign' => new CampaignResource($campaign->load(['brand', 'pic', 'creator', 'members'])),
        ]);
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $user = request()->user();
        $campaignName = $campaign->name;
        $campaign->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Campaign \"{$campaignName}\" was deleted.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $campaign,
            actorId: $user->id
        );

        return $this->success('Campaign deleted successfully.');
    }
}
