<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserPreferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * User Preference Controller — dashboard personalization (Sprint 11).
 */
class UserPreferenceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserPreferenceService $service = new UserPreferenceService()
    ) {}

    /**
     * GET /api/v1/admin/me/preferences
     */
    public function show(Request $request): JsonResponse
    {
        $prefs = $this->service->getPreferences($request->user()->id);
        return $this->success('Preferences retrieved.', ['preferences' => $prefs]);
    }

    /**
     * PUT /api/v1/admin/me/preferences
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'default_landing_page' => ['sometimes', 'string', 'max:100'],
            'default_page_size'    => ['sometimes', 'integer', 'in:10,15,25,50,100'],
            'theme'                => ['sometimes', 'in:dark,light,system'],
            'locale'               => ['sometimes', 'in:id,en'],
            'timezone'             => ['sometimes', 'string', 'max:50'],
            'dashboard_widgets'    => ['sometimes', 'array'],
            'extended'             => ['sometimes', 'array'],
        ]);

        $prefs = $this->service->updatePreferences($request->user()->id, $request->only([
            'default_landing_page', 'default_page_size', 'theme', 'locale', 'timezone',
            'dashboard_widgets', 'extended',
        ]));
        return $this->success('Preferences updated.', ['preferences' => $prefs]);
    }
}
