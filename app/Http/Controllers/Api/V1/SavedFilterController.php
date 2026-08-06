<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserPreferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Saved Filter Controller — per-user, per-module persistent filter management (Sprint 11).
 */
class SavedFilterController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserPreferenceService $service = new UserPreferenceService()
    ) {}

    /**
     * GET /api/v1/admin/saved-filters?module=campaigns
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'module' => ['required', 'in:campaigns,promotions,products,variants,activity_logs'],
        ]);

        $filters = $this->service->getSavedFilters($request->user()->id, $request->input('module'));
        return $this->success('Saved filters retrieved.', ['filters' => $filters]);
    }

    /**
     * POST /api/v1/admin/saved-filters
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'module'     => ['required', 'in:campaigns,promotions,products,variants,activity_logs'],
            'name'       => ['required', 'string', 'max:100'],
            'filters'    => ['required', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $filter = $this->service->saveFilter(
            $request->user()->id,
            $request->input('module'),
            $request->input('name'),
            $request->input('filters'),
            (bool) $request->input('is_default', false)
        );

        return $this->success('Saved filter created.', ['filter' => $filter], 201);
    }

    /**
     * DELETE /api/v1/admin/saved-filters/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $deleted = $this->service->deleteFilter($id, $request->user()->id);

        if (!$deleted) {
            return $this->error('Saved filter not found or unauthorized.', [], 404);
        }

        return $this->success('Saved filter deleted.');
    }

    /**
     * PATCH /api/v1/admin/saved-filters/{id}/set-default
     */
    public function setDefault(Request $request, string $id): JsonResponse
    {
        $filter = $this->service->setDefaultFilter($id, $request->user()->id);
        return $this->success('Default filter updated.', ['filter' => $filter]);
    }
}
