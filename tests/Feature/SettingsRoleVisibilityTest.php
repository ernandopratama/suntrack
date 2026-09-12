<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SettingsRoleVisibilityTest extends TestCase
{
    public function test_settings_navigation_is_available_to_all_internal_roles(): void
    {
        $layout = File::get(resource_path('js/layouts/AdminLayout.vue'));
        $router = File::get(resource_path('js/router.js'));

        $this->assertStringContainsString(
            'v-if="$hasRole(\'Super Admin\') || $hasRole(\'Admin\') || $hasRole(\'Tim\')"',
            $layout
        );
        $this->assertStringContainsString(
            "meta: { roles: ['Super Admin', 'Admin', 'Tim'] }",
            $router
        );
        $this->assertStringContainsString(
            'to.meta.roles.some((role) => authStore.hasRole(role))',
            $router
        );
    }

    public function test_admin_and_team_receive_device_settings_without_loading_system_settings(): void
    {
        $settings = File::get(resource_path('js/pages/SystemSettings.vue'));

        $this->assertStringContainsString('v-if="!isSystemAdministrator"', $settings);
        $this->assertStringContainsString('Profil Saya', $settings);
        $this->assertStringContainsString("api.patch('/auth/profile', profileForm)", $settings);
        $this->assertStringContainsString('Pasang di Smartphone', $settings);
        $this->assertStringContainsString('if (isSystemAdministrator.value)', $settings);
    }
}
