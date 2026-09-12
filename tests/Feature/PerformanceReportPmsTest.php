<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Company;
use App\Models\PerformanceReport;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerformanceReportPmsTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private User $creator;

    private User $otherAdmin;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $company = Company::create(['name' => 'PMS Company']);
        $this->brand = Brand::create(['company_id' => $company->id, 'name' => 'Suurlemon']);

        $this->creator = User::factory()->create();
        $this->creator->assignRole(RbacRegistry::TEAM);
        $this->creator->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $this->creator->assignedBrands()->attach($this->brand->id, ['assigned_by' => $this->creator->id]);

        $this->otherAdmin = User::factory()->create();
        $this->otherAdmin->assignRole(RbacRegistry::ADMIN);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole(RbacRegistry::SUPER_ADMIN);
    }

    public function test_report_is_created_as_draft_with_logged_in_pic_metrics_and_inactive_link(): void
    {
        $response = $this->actingAs($this->creator)->postJson('/api/v1/admin/performance-reports', $this->payload([
            'pic_id' => $this->otherAdmin->id,
            'executive_summary' => '<p onclick="alert(1)">Baik<script>alert(1)</script></p>',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.report.status', 'draft')
            ->assertJsonPath('data.report.pic_id', $this->creator->id)
            ->assertJsonPath('data.report.roas', 3.3)
            ->assertJsonPath('data.report.secure_link.status', 'Revoked');

        $summary = $response->json('data.report.executive_summary');
        $this->assertSame('<p>Baik</p>', $summary);
        $this->assertDatabaseHas('secure_links', [
            'linkable_id' => $response->json('data.report.id'),
            'created_by' => $this->creator->id,
        ]);
    }

    public function test_period_rules_enforce_daily_weekly_and_calendar_month_limits(): void
    {
        $this->actingAs($this->creator)->postJson('/api/v1/admin/performance-reports', $this->payload([
            'period_end' => '2026-09-12',
        ]))->assertJsonValidationErrors('period_end');

        $this->actingAs($this->creator)->postJson('/api/v1/admin/performance-reports', $this->payload([
            'report_type' => 'weekly',
            'period_end' => '2026-09-18',
        ]))->assertJsonValidationErrors('period_end');

        $this->actingAs($this->creator)->postJson('/api/v1/admin/performance-reports', $this->payload([
            'report_type' => 'monthly',
            'period_start' => '2026-09-20',
            'period_end' => '2026-10-01',
        ]))->assertJsonValidationErrors('period_end');

        $this->actingAs($this->creator)->postJson('/api/v1/admin/performance-reports', $this->payload([
            'report_type' => 'weekly',
            'period_end' => '2026-09-17',
        ]))->assertCreated();
    }

    public function test_only_creator_and_super_admin_can_edit_report_fields(): void
    {
        $report = $this->createReport();

        $this->actingAs($this->otherAdmin)->putJson("/api/v1/admin/performance-reports/{$report->id}", [
            'title' => 'Tidak Diizinkan',
        ])->assertForbidden();

        $this->actingAs($this->creator)->putJson("/api/v1/admin/performance-reports/{$report->id}", [
            'title' => 'Diedit Pembuat',
        ])->assertOk();

        $this->actingAs($this->superAdmin)->putJson("/api/v1/admin/performance-reports/{$report->id}", [
            'title' => 'Diedit Super Admin',
        ])->assertOk();
    }

    public function test_image_publish_and_public_secure_report_use_the_same_token(): void
    {
        Storage::fake('local');
        $report = $this->createReport();
        $draftToken = $report->secureLinks()->firstOrFail()->token;

        $mediaResponse = $this->actingAs($this->creator)->post("/api/v1/admin/performance-reports/{$report->id}/media", [
            'image' => UploadedFile::fake()->image('shopee.png', 1000, 700),
            'title' => 'Performa Shopee',
            'notes' => '<p>Omzet meningkat.</p>',
        ])->assertCreated();

        $published = $this->actingAs($this->creator)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.report.status', 'published')
            ->assertJsonPath('data.report.secure_link.status', 'Active');

        $this->assertSame($draftToken, $published->json('data.report.secure_link.token'));
        $this->getJson('/api/v1/public/review/'.$draftToken)
            ->assertOk()
            ->assertJsonPath('data.type', 'PerformanceReport')
            ->assertJsonPath('data.media.0.title', 'Performa Shopee');
        $this->get('/api/v1/public/review/'.$draftToken.'/media/'.$mediaResponse->json('data.media.id'))
            ->assertOk();
    }

    public function test_global_secure_link_list_is_super_admin_only(): void
    {
        $this->createReport();
        $this->actingAs($this->creator)->getJson('/api/v1/admin/performance-report-links')->assertForbidden();
        $this->actingAs($this->otherAdmin)->getJson('/api/v1/admin/performance-report-links')->assertForbidden();
        $this->actingAs($this->superAdmin)->getJson('/api/v1/admin/performance-report-links')
            ->assertOk()->assertJsonCount(1, 'data.links.data');
    }

    private function createReport(): PerformanceReport
    {
        $id = $this->actingAs($this->creator)
            ->postJson('/api/v1/admin/performance-reports', $this->payload())
            ->assertCreated()->json('data.report.id');

        return PerformanceReport::findOrFail($id);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'brand_id' => $this->brand->id,
            'report_type' => 'daily',
            'title' => 'Daily Report Suurlemon',
            'period_start' => '2026-09-11',
            'period_end' => '2026-09-11',
            'turnover' => 957549,
            'order_count' => 12,
            'ad_spend' => 290419,
            'ad_sales' => 957549,
            'executive_summary' => '<p>Ringkasan</p>',
            'content' => '<p>Analisis</p>',
            'findings' => '<p>Temuan</p>',
            'action_plan' => '<p>Tindak lanjut</p>',
        ], $overrides);
    }
}
