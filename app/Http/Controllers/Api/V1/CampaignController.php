<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Brand;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignRepository $repository,
        protected DataScopeService $dataScope
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
            filters: $request->only(['search', 'status']),
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

        $campaign = Campaign::create($data);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: 'Campaign "'.$campaign->name.'" was created.',
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $campaign,
            actorId: $user->id
        );

        return $this->success('Campaign created successfully.', [
            'campaign' => new CampaignResource($campaign),
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $campaign->load(['pic', 'comments']);

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

        $oldStatus = $campaign->status;
        $campaign->update($request->validated());

        $user = $request->user();

        // Log if status changed
        if ($oldStatus !== $campaign->status) {
            ActivityLogger::log(
                action: ActivityType::StatusChanged->value,
                description: "Campaign status changed from {$oldStatus} to {$campaign->status}.",
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $campaign,
                actorId: $user->id,
                properties: ['old_status' => $oldStatus, 'new_status' => $campaign->status]
            );
        } else {
            ActivityLogger::log(
                action: ActivityType::Updated->value,
                description: 'Campaign "'.$campaign->name.'" was updated.',
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $campaign,
                actorId: $user->id
            );
        }

        return $this->success('Campaign updated successfully.', [
            'campaign' => new CampaignResource($campaign),
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
