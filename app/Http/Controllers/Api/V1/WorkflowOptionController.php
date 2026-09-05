<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Support\Rbac\RbacRegistry;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowOptionController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_id' => ['required', 'uuid', 'exists:brands,id'],
        ]);
        $actor = $request->user();

        abort_unless($actor->canAny([
            'campaign.create', 'campaign.update', 'task.create', 'task.update',
            'performance-report.create', 'performance-report.update',
        ]), 403);

        $scope = app(DataScopeService::class);
        if (! $scope->canAccessBrandId($actor, $data['brand_id'])) {
            abort(404);
        }

        $pics = User::role([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN])
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $members = User::role(RbacRegistry::TEAM)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $scope->canAccessBrandId($user, $data['brand_id']))
            ->values();

        $campaigns = $scope->scopeCampaigns(Campaign::query(), $actor)
            ->where('brand_id', $data['brand_id'])
            ->select(['id', 'name', 'status'])
            ->orderBy('name')
            ->get();

        return $this->success('Workflow options retrieved successfully.', [
            'pics' => $pics,
            'team_members' => $members,
            'campaigns' => $campaigns,
        ]);
    }
}
