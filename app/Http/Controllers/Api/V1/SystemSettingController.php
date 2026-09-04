<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Settings\SettingsService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    use ApiResponse;

    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index(Request $request)
    {
        $group = $request->query('group');
        if ($group) {
            return $this->success("Settings retrieved for group: {$group}", $this->settingsService->getGroup($group));
        }

        return $this->success('All system settings retrieved successfully.', $this->settingsService->all());
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'nullable|string',
            'settings.*.group' => 'nullable|string',
            'settings.*.description' => 'nullable|string',
            'settings.*.is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error.', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $updated = [];
            foreach ($request->input('settings') as $item) {
                $setting = $this->settingsService->set(
                    $item['key'],
                    $item['value'] ?? null,
                    $item['type'] ?? 'string',
                    $item['group'] ?? 'general',
                    $item['description'] ?? null,
                    $item['is_public'] ?? false
                );
                $updated[] = $setting;
            }

            ActivityLogger::log(
                'SystemSettings:Update',
                'Updated '.count($updated).' system setting keys.',
                'Admin',
                $request->user()->name ?? 'System Admin',
                null,
                $request->user()?->id,
                null,
                ['keys' => array_column($request->input('settings'), 'key')]
            );

            DB::commit();

            return $this->success('System settings updated successfully.', $this->settingsService->all());
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to update settings: '.$e->getMessage(), [], 500);
        }
    }

    public function publicSettings()
    {
        return $this->success('Public settings retrieved successfully.', $this->settingsService->getPublicSettings());
    }
}
