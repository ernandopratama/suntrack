<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_update_own_name_and_username(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Lama',
            'username' => 'admin.lama',
            'email' => 'admin@example.test',
        ]);
        $admin->assignRole(RbacRegistry::ADMIN);

        $this->actingAs($admin)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Admin Baru',
                'username' => 'Admin.Baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Admin Baru')
            ->assertJsonPath('data.user.username', 'admin.baru');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Baru',
            'username' => 'admin.baru',
            'email' => 'admin@example.test',
        ]);
    }

    public function test_team_cannot_use_an_existing_name_or_username(): void
    {
        User::factory()->create([
            'name' => 'Nama Terpakai',
            'username' => 'username.terpakai',
        ]);

        $team = User::factory()->create([
            'name' => 'Nama Tim',
            'username' => 'nama.tim',
        ]);
        $team->assignRole(RbacRegistry::TEAM);

        $this->actingAs($team)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Nama Terpakai',
                'username' => 'username.terpakai',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'username']);

        $this->assertDatabaseHas('users', [
            'id' => $team->id,
            'name' => 'Nama Tim',
            'username' => 'nama.tim',
        ]);
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $team = User::factory()->create([
            'name' => 'Anggota Tim',
            'username' => 'anggota.tim',
            'password' => 'password-lama',
        ]);
        $team->assignRole(RbacRegistry::TEAM);

        $this->actingAs($team)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Anggota Tim',
                'username' => 'anggota.tim',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($team)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Anggota Tim',
                'username' => 'anggota.tim',
                'current_password' => 'password-lama',
                'password' => 'password-baru',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->actingAs($team)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Anggota Tim',
                'username' => 'anggota.tim',
                'current_password' => 'password-lama',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('password-baru', $team->fresh()->password));
    }
}
