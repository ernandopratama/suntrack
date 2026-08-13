<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignRepository $repository
    ) {}

    /**
     * Display a listing of the campaigns.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $campaigns = $this->repository->getFilteredPaginated(
            companyId: $user->hasRole('Super Admin') ? null : $user->company_id,
            filters: $request->only(["search", "status"]),
            perPage: (int) $request->get("per_page", 15)
        );

        return $this->success("Campaigns retrieved successfully.", [
            "campaigns" => CampaignResource::collection($campaigns)->response()->getData(true)
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();

        // If brand_id is not provided, use the user"s default company"s first brand
        if (!isset($data["brand_id"]) || is_null($data["brand_id"])) {
            $data["brand_id"] = $user->company->brands()->first()->id;
        }

        $campaign = Campaign::create($data);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Campaign \"" . $campaign->name . "\" was created.",
            actorType: "Admin",
            actorName: $user->name,
            loggable: $campaign,
            actorId: $user->id
        );

        return $this->success("Campaign created successfully.", [
            "campaign" => new CampaignResource($campaign)
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $user = request()->user();

        // Super Admin bisa lihat semua
        if ($user->hasRole('Super Admin')) {
            $campaign->load(['pic', 'comments']);
            return $this->success('Campaign retrieved successfully.', [
                'campaign' => new CampaignResource($campaign)
            ]);
        }

        // User lain hanya bisa lihat campaign dari company yang sama
        if ($campaign->brand && $campaign->brand->company_id !== $user->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $campaign->load(['pic', 'comments']);

        return $this->success('Campaign retrieved successfully.', [
            'campaign' => new CampaignResource($campaign)
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        if (! request()->user()->hasRole('Super Admin') && $campaign->brand?->company_id !== request()->user()->company_id) {
            return $this->error("Unauthorized.", [], 403);
        }

        $oldStatus = $campaign->status;
        $campaign->update($request->validated());

        $user = $request->user();

        // Log if status changed
        if ($oldStatus !== $campaign->status) {
            ActivityLogger::log(
                action: ActivityType::StatusChanged->value,
                description: "Campaign status changed from {$oldStatus} to {$campaign->status}.",
                actorType: "Admin",
                actorName: $user->name,
                loggable: $campaign,
                actorId: $user->id,
                properties: ["old_status" => $oldStatus, "new_status" => $campaign->status]
            );
        } else {
            ActivityLogger::log(
                action: ActivityType::Updated->value,
                description: "Campaign \"" . $campaign->name . "\" was updated.",
                actorType: "Admin",
                actorName: $user->name,
                loggable: $campaign,
                actorId: $user->id
            );
        }

        return $this->success("Campaign updated successfully.", [
            "campaign" => new CampaignResource($campaign)
        ]);
    }
}
