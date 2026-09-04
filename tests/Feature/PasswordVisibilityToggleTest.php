<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PasswordVisibilityToggleTest extends TestCase
{
    public function test_sensitive_inputs_have_accessible_visibility_toggles(): void
    {
        $cases = [
            'js/components/UserForm.vue' => 'showUserPassword',
            'js/pages/SystemSettings.vue' => 'showWaToken',
            'js/pages/Login.vue' => 'showLoginPassword',
        ];

        foreach ($cases as $path => $state) {
            $source = File::get(resource_path($path));

            $this->assertStringContainsString(":type=\"{$state} ? 'text' : 'password'\"", $source);
            $this->assertStringContainsString(":aria-pressed=\"{$state}\"", $source);
            $this->assertStringContainsString("@click=\"{$state} = !{$state}\"", $source);
            $this->assertStringContainsString('fa-eye-slash', $source);
        }
    }
}
