<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Services\Storage\StorageService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Sprint9ProductionReadyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $company = Company::create(['name' => 'Test Company']);
        $this->adminUser = User::create([
            'company_id' => $company->id,
            'name' => 'Super Admin Tester',
            'email' => 'admin@suntrack.com',
            'password' => bcrypt('password'),
        ]);

        $this->adminUser->assignRole('Super Admin');
    }

    public function test_health_check_endpoint_returns_ok_status()
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'timestamp',
                    'services' => ['database', 'cache', 'storage'],
                    'environment',
                ],
            ]);

        $this->assertEquals('healthy', $response->json('data.status'));
        $this->assertEquals('ok', $response->json('data.services.database'));
    }

    public function test_system_settings_management_and_caching()
    {
        $settingsService = app(SettingsService::class);
        $settingsService->set('app.name', 'SunTrack Pro', 'string', 'general', 'App name', true);

        // Public settings check
        $publicResponse = $this->getJson('/api/v1/public/settings');
        $publicResponse->assertStatus(200);
        $this->assertEquals('SunTrack Pro', $publicResponse->json()['data']['app.name']);

        // Admin update settings
        $updateResponse = $this->actingAs($this->adminUser)->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'app.name', 'value' => 'SunTrack Enterprise', 'type' => 'string', 'group' => 'general', 'is_public' => true],
                ['key' => 'security.rate_limit_api', 'value' => 120, 'type' => 'integer', 'group' => 'security', 'is_public' => false],
            ]
        ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('SunTrack Enterprise', $settingsService->get('app.name'));
        $this->assertEquals(120, $settingsService->get('security.rate_limit_api'));
    }

    public function test_rbac_roles_and_permissions()
    {
        $this->assertTrue($this->adminUser->hasRole('Super Admin'));
        $this->assertTrue($this->adminUser->hasPermissionTo('settings.update'));
        $this->assertTrue($this->adminUser->hasPermissionTo('campaign.create'));
    }

    public function test_media_storage_abstraction_service()
    {
        $storageService = app(StorageService::class);
        $testPath = 'unit_tests/test_' . time() . '.txt';

        $this->assertTrue($storageService->put($testPath, 'Hello Storage'));
        $this->assertTrue($storageService->exists($testPath));
        $this->assertEquals('Hello Storage', $storageService->get($testPath));
        $this->assertTrue($storageService->delete($testPath));
        $this->assertFalse($storageService->exists($testPath));

        $this->assertEquals('local', $storageService->getDriverName());
    }

    public function test_automated_database_backup_command()
    {
        $exitCode = Artisan::call('suntrack:backup-db');
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup successfully completed', $output);
    }
}
