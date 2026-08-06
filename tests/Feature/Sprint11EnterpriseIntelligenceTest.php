<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\NotificationLog;
use App\Models\SavedFilter;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 11: Enterprise Intelligence Feature Tests.
 */
class Sprint11EnterpriseIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // 1. Global Search Engine Tests
    // -------------------------------------------------------

    public function test_global_search_validates_minimum_query_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/search?q=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_global_search_returns_grouped_results(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/search?q=benchmark');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['results', 'driver', 'query', 'total'],
            ]);
    }

    public function test_global_search_driver_is_database_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/search?q=test-query-xyz');
        $response->assertStatus(200);

        $data = $response->json('data.driver');
        $this->assertEquals('database', $data);
    }

    // -------------------------------------------------------
    // 2. Audit Dashboard Tests
    // -------------------------------------------------------

    public function test_audit_summary_returns_expected_keys(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/audit/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['logins_today', 'failed_logins_today', 'total_failed_jobs', 'total_pending_jobs', 'error_log_size_kb'],
            ]);
    }

    public function test_audit_login_history_returns_paginated_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/audit/login-history');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['login_history']]);
    }

    public function test_audit_queue_history_returns_expected_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/audit/queue-history');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['failed', 'pending', 'summary']]);
    }

    // -------------------------------------------------------
    // 3. Notification Center Tests
    // -------------------------------------------------------

    public function test_notification_center_index_returns_paginated_list(): void
    {
        $user = User::factory()->create();
        NotificationLog::create([
            'type'      => 'email',
            'recipient' => 'test@suntrack.id',
            'body'      => 'Test notification',
            'status'    => 'sent',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/admin/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['notifications']]);
    }

    public function test_notification_center_summary_returns_totals(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/notifications/summary');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['totals', 'breakdown']]);
    }

    public function test_notification_retry_rejects_non_failed_status(): void
    {
        $user = User::factory()->create();
        $log  = NotificationLog::create([
            'type'      => 'whatsapp',
            'recipient' => '+6281234567890',
            'body'      => 'Hello',
            'status'    => 'sent',  // Cannot retry sent notifications
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/admin/notifications/{$log->id}/retry");

        $response->assertStatus(422);
    }

    public function test_notification_state_machine_transitions(): void
    {
        $log = NotificationLog::create([
            'type'      => 'email',
            'recipient' => 'test@test.com',
            'body'      => 'Body',
            'status'    => 'pending',
        ]);

        $log->markProcessing();
        $this->assertEquals('processing', $log->fresh()->status);

        $log->markSent();
        $this->assertEquals('sent', $log->fresh()->status);

        // A sent notification cannot retry
        $this->assertFalse($log->fresh()->canRetry());
    }

    // -------------------------------------------------------
    // 4. System Monitoring Tests
    // -------------------------------------------------------

    public function test_system_health_returns_overall_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/system/health');

        // Accept 200 (healthy) or 503 (degraded) — both are valid responses
        $this->assertContains($response->status(), [200, 503]);
        $response->assertJsonStructure(['data' => ['overall_status', 'checks', 'timestamp']]);
    }

    public function test_system_cache_stats_returns_hit_miss_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/system/cache-stats');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['hit_miss_ratio', 'api_stats', 'memory', 'driver']]);
    }

    // -------------------------------------------------------
    // 5. Saved Filters Tests
    // -------------------------------------------------------

    public function test_saved_filters_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/admin/saved-filters', [
            'module'  => 'campaigns',
            'name'    => 'Active Campaigns',
            'filters' => ['status' => 'Running', 'brand_id' => 'abc'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['filter' => ['id', 'name', 'module', 'filters']]]);
    }

    public function test_saved_filters_can_be_listed_by_module(): void
    {
        $user = User::factory()->create();
        SavedFilter::create([
            'user_id' => $user->id,
            'module'  => 'promotions',
            'name'    => 'Pending Promos',
            'filters' => ['status' => 'Pending'],
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/admin/saved-filters?module=promotions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['filters']]);
        $this->assertCount(1, $response->json('data.filters'));
    }

    public function test_saved_filters_can_be_deleted(): void
    {
        $user   = User::factory()->create();
        $filter = SavedFilter::create([
            'user_id' => $user->id,
            'module'  => 'products',
            'name'    => 'Active Products',
            'filters' => ['status' => 'Active'],
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/admin/saved-filters/{$filter->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('saved_filters', ['id' => $filter->id]);
    }

    // -------------------------------------------------------
    // 6. User Preferences Tests
    // -------------------------------------------------------

    public function test_user_preferences_are_created_with_defaults_on_first_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/admin/me/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('data.preferences.theme', 'dark')
            ->assertJsonPath('data.preferences.locale', 'id')
            ->assertJsonPath('data.preferences.default_page_size', 15);
    }

    public function test_user_preferences_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/admin/me/preferences', [
            'theme'             => 'light',
            'default_page_size' => 25,
            'locale'            => 'en',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.preferences.theme', 'light')
            ->assertJsonPath('data.preferences.default_page_size', 25);
    }

    public function test_user_preferences_rejects_invalid_theme(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/admin/me/preferences', [
            'theme' => 'rainbow',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['theme']);
    }
}
