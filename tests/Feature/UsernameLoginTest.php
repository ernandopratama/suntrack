<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_internal_routes_load_the_web_session_middleware(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $this->assertContains(
                'web',
                $route->middleware(),
                "Route [{$route->uri()}] must load the web session middleware."
            );
        }
    }

    public function test_login_session_authenticates_an_internal_request_without_browser_origin_headers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'username' => 'session.user',
            'password' => 'password',
        ]);
        $user->assignRole(RbacRegistry::SUPER_ADMIN);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'session.user',
            'password' => 'password',
        ])->assertOk();

        app('auth')->forgetGuards();

        $this->getJson('/api/v1/admin/dashboard/stats')->assertOk();
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'budi.santoso',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'budi.santoso',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.username', 'budi.santoso');
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_email_login_payload_remains_supported(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy@suntrack.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@suntrack.test',
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_username_is_rejected(): void
    {
        User::factory()->create([
            'username' => 'valid.user',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'unknown.user',
            'password' => 'password',
        ])->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_user_can_be_updated_without_changing_email(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $actor = User::factory()->create();
        $actor->assignRole(RbacRegistry::SUPER_ADMIN);
        $user = User::factory()->create([
            'name' => 'Existing User',
            'username' => 'existing.user',
            'email' => 'existing@suntrack.test',
        ]);

        $response = $this->actingAs($actor)->putJson("/api/v1/admin/users/{$user->id}", [
            'name' => 'Updated User',
            'username' => 'existing.user',
            'email' => 'existing@suntrack.test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'existing@suntrack.test')
            ->assertJsonPath('data.user.username', 'existing.user');
    }
}
