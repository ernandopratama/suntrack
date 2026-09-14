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
use Illuminate\Support\Facades\File;
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
        $expiresAt = $response->json('data.report.secure_link.expires_at');
        $this->assertNotNull($expiresAt);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, strtotime($expiresAt), 2);
        $this->assertDatabaseHas('secure_links', [
            'linkable_id' => $response->json('data.report.id'),
            'created_by' => $this->creator->id,
        ]);
    }

    public function test_period_rules_enforce_daily_weekly_and_monthly_limits(): void
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
            'period_end' => '2026-10-20',
        ]))->assertJsonValidationErrors('period_end');

        $this->actingAs($this->creator)->postJson('/api/v1/admin/performance-reports', $this->payload([
            'report_type' => 'monthly',
            'period_start' => '2026-09-20',
            'period_end' => '2026-10-19',
        ]))->assertCreated();

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

    public function test_team_only_sees_its_own_performance_reports(): void
    {
        $ownReport = $this->createReport();
        $otherTeam = User::factory()->create();
        $otherTeam->assignRole(RbacRegistry::TEAM);
        $otherTeam->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $otherTeam->assignedBrands()->attach($this->brand->id, ['assigned_by' => $this->superAdmin->id]);

        $otherReportId = $this->actingAs($otherTeam)
            ->postJson('/api/v1/admin/performance-reports', $this->payload(['title' => 'Laporan Tim Lain']))
            ->assertCreated()
            ->json('data.report.id');

        $this->actingAs($this->creator)
            ->getJson('/api/v1/admin/performance-reports')
            ->assertOk()
            ->assertJsonCount(1, 'data.reports.data')
            ->assertJsonPath('data.reports.data.0.id', $ownReport->id);

        $this->actingAs($this->creator)
            ->getJson("/api/v1/admin/performance-reports/{$otherReportId}")
            ->assertForbidden();

        $this->actingAs($this->otherAdmin)
            ->getJson('/api/v1/admin/performance-reports')
            ->assertOk()
            ->assertJsonCount(2, 'data.reports.data');
    }

    public function test_image_publish_and_public_secure_report_use_the_same_token(): void
    {
        Storage::fake('local');
        $report = $this->createReport();
        $draftToken = $report->secureLinks()->firstOrFail()->token;

        $this->assertLessThanOrEqual(64, strlen($draftToken));
        $this->assertMatchesRegularExpression(
            '/^suurlemon-daily-\d{8}-[A-Za-z0-9]{22}$/',
            $draftToken,
        );
        $this->assertStringContainsString($report->created_at->format('Ymd'), $draftToken);

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
        $this->assertSame(url('/r/'.$draftToken), $published->json('data.report.secure_link.url'));
        $this->getJson('/api/v1/public/review/'.$draftToken)
            ->assertOk()
            ->assertJsonPath('data.type', 'PerformanceReport')
            ->assertJsonPath('data.media.0.title', 'Performa Shopee');
        $this->get('/api/v1/public/review/'.$draftToken.'/media/'.$mediaResponse->json('data.media.id'))
            ->assertOk();
    }

    public function test_short_public_route_keeps_the_legacy_route_as_an_alias(): void
    {
        $router = File::get(resource_path('js/router.js'));

        $this->assertStringContainsString("path: '/r/:token'", $router);
        $this->assertStringContainsString("alias: '/review/:token'", $router);
    }

    public function test_global_secure_link_list_is_super_admin_only(): void
    {
        $this->createReport();
        $this->actingAs($this->creator)->getJson('/api/v1/admin/performance-report-links')->assertForbidden();
        $this->actingAs($this->otherAdmin)->getJson('/api/v1/admin/performance-report-links')->assertForbidden();
        $this->actingAs($this->superAdmin)->getJson('/api/v1/admin/performance-report-links')
            ->assertOk()->assertJsonCount(1, 'data.links.data');

        $page = File::get(resource_path('js/pages/PerformanceReportLinks.vue'));
        $this->assertStringContainsString('Berlaku sampai {{ formatExpiry(link.expires_at) }}', $page);
        $this->assertStringContainsString('aria-label="Salin link"', $page);
        $this->assertStringContainsString('aria-label="Buka link"', $page);
        $this->assertStringContainsString('aria-label="Nonaktifkan link"', $page);
        $this->assertStringContainsString('aria-label="Aktifkan link"', $page);
        $this->assertStringContainsString('role="tooltip"', $page);
        $this->assertStringNotContainsString('>Salin</button>', $page);
        $this->assertStringNotContainsString('>Buka</a>', $page);
        $this->assertStringNotContainsString('>Nonaktifkan</button>', $page);
        $this->assertStringNotContainsString('>Aktifkan</button>', $page);
    }

    public function test_super_admin_can_reactivate_revoked_and_expired_report_links(): void
    {
        $report = $this->createReport();
        $this->actingAs($this->creator)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/publish")
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/v1/admin/performance-reports/{$report->id}/secure-link")
            ->assertOk()
            ->assertJsonPath('data.status', 'Revoked');

        $reactivated = $this->actingAs($this->superAdmin)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/secure-link")
            ->assertOk()
            ->assertJsonPath('data.status', 'Active');
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, strtotime($reactivated->json('data.expires_at')), 2);

        $link = $report->secureLinks()->firstOrFail();
        $link->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->getJson('/api/v1/public/review/'.$link->token)
            ->assertForbidden()
            ->assertJsonPath('status', 'Expired');

        $renewed = $this->actingAs($this->superAdmin)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/secure-link")
            ->assertOk()
            ->assertJsonPath('data.status', 'Active');
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, strtotime($renewed->json('data.expires_at')), 2);
        $this->getJson('/api/v1/public/review/'.$link->token)->assertOk();
    }

    public function test_authorized_users_can_permanently_delete_reports_and_all_related_data(): void
    {
        Storage::fake('local');
        $report = $this->createReport();
        $mediaId = $this->actingAs($this->creator)
            ->post("/api/v1/admin/performance-reports/{$report->id}/media", [
                'image' => UploadedFile::fake()->image('report.png'),
            ])
            ->assertCreated()
            ->json('data.media.id');
        $mediaPath = $report->media()->findOrFail($mediaId)->path;

        $directPath = "attachments/performance-report/{$report->id}/brief.txt";
        Storage::disk('local')->put($directPath, 'brief');
        $directAttachment = $report->attachments()->create([
            'uploaded_by' => $this->creator->id,
            'disk' => 'local',
            'path' => $directPath,
            'original_name' => 'brief.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);
        $comment = $report->comments()->create([
            'user_id' => $this->creator->id,
            'author_name' => $this->creator->name,
            'author_type' => 'Tim',
            'body' => 'Komentar laporan',
        ]);
        $commentPath = "attachments/comment/{$comment->id}/evidence.txt";
        Storage::disk('local')->put($commentPath, 'evidence');
        $commentAttachment = $comment->attachments()->create([
            'uploaded_by' => $this->creator->id,
            'disk' => 'local',
            'path' => $commentPath,
            'original_name' => 'evidence.txt',
            'mime_type' => 'text/plain',
            'size' => 8,
        ]);

        $this->actingAs($this->creator)
            ->postJson("/api/v1/admin/performance-reports/{$report->id}/publish")
            ->assertOk();
        $link = $report->secureLinks()->firstOrFail();
        $accessLog = $link->accessLogs()->create(['accessed_at' => now()]);
        $activityLogIds = $report->activityLogs()->pluck('id');

        $otherTeam = User::factory()->create();
        $otherTeam->assignRole(RbacRegistry::TEAM);
        $otherTeam->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $otherTeam->assignedBrands()->attach($this->brand->id, ['assigned_by' => $this->superAdmin->id]);
        $this->actingAs($otherTeam)
            ->deleteJson("/api/v1/admin/performance-reports/{$report->id}")
            ->assertForbidden();

        $this->actingAs($this->otherAdmin)
            ->deleteJson("/api/v1/admin/performance-reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Laporan dan seluruh data terkait berhasil dihapus permanen.');

        $this->assertNull(PerformanceReport::withTrashed()->find($report->id));
        $this->assertDatabaseMissing('performance_report_media', ['id' => $mediaId]);
        $this->assertDatabaseMissing('secure_links', ['id' => $link->id]);
        $this->assertDatabaseMissing('secure_link_access_logs', ['id' => $accessLog->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $directAttachment->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $commentAttachment->id]);
        foreach ($activityLogIds as $activityLogId) {
            $this->assertDatabaseMissing('activity_logs', ['id' => $activityLogId]);
        }
        Storage::disk('local')->assertMissing($mediaPath);
        Storage::disk('local')->assertMissing($directPath);
        Storage::disk('local')->assertMissing($commentPath);

        $page = File::get(resource_path('js/pages/PerformanceReports.vue'));
        $this->assertStringContainsString('v-if="report.can_delete"', $page);
        $this->assertStringContainsString('aria-label="Salin Secure Link"', $page);
        $this->assertStringContainsString('aria-label="Edit laporan"', $page);
        $this->assertStringContainsString('aria-label="Publikasikan laporan"', $page);
        $this->assertStringContainsString('aria-label="Hapus laporan"', $page);
        $this->assertStringContainsString('role="tooltip"', $page);
        $this->assertStringNotContainsString('>Salin</button>', $page);
        $this->assertStringNotContainsString('>Edit</button>', $page);
        $this->assertStringNotContainsString('>Publish</button>', $page);
        $this->assertStringNotContainsString("report.can_delete && report.status !== 'published'", $page);

        $teamReport = $this->createReport();
        $this->actingAs($this->creator)
            ->deleteJson("/api/v1/admin/performance-reports/{$teamReport->id}")
            ->assertOk();

        $superAdminReport = $this->createReport();
        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/v1/admin/performance-reports/{$superAdminReport->id}")
            ->assertOk();
    }

    public function test_money_metrics_use_automatic_rupiah_inputs(): void
    {
        $page = File::get(resource_path('js/pages/PerformanceReports.vue'));

        $this->assertStringContainsString("label: 'Omset / Penjualan Toko', currency: true", $page);
        $this->assertStringContainsString("label: 'Budget Ads Terpakai (Biaya Iklan)', currency: true", $page);
        $this->assertStringContainsString("label: 'Penjualan dari Iklan', currency: true", $page);
        $this->assertStringContainsString('Rp.</span>', $page);
        $this->assertStringContainsString(':value="formatRupiahInput(form[field.key])"', $page);
        $this->assertStringContainsString('@input="updateCurrencyField(field.key, $event)"', $page);
        $this->assertStringContainsString('type="tel"', $page);
        $this->assertStringContainsString('pattern="[0-9.]*"', $page);
        $this->assertStringNotContainsString('@pointerdown="focusMobileInput"', $page);
    }

    public function test_rich_text_editor_supports_mobile_keyboard_focus(): void
    {
        $editor = File::get(resource_path('js/components/RichTextEditor.vue'));

        $this->assertStringContainsString("import Quill from 'quill'", $editor);
        $this->assertStringContainsString('new Quill(editor.value', $editor);
        $this->assertStringContainsString("quill.root.setAttribute('inputmode', 'text')", $editor);
        $this->assertStringContainsString("quill.on('text-change', emitValue)", $editor);
        $this->assertStringNotContainsString('document.execCommand', $editor);
        $this->assertStringNotContainsString('pointerdown', $editor);
    }

    public function test_report_form_fills_period_end_from_report_duration(): void
    {
        $page = File::get(resource_path('js/pages/PerformanceReports.vue'));

        $this->assertStringContainsString('REPORT_DURATION_DAYS = { daily: 1, weekly: 7, monthly: 30 }', $page);
        $this->assertStringContainsString('@change="syncPeriodEnd"', $page);
        $this->assertStringContainsString('day + duration - 1', $page);
        $this->assertStringContainsString('form.period_end = endDate.toISOString().slice(0, 10)', $page);
    }

    public function test_publish_validates_required_fields_and_displays_api_errors_inside_modal(): void
    {
        $page = File::get(resource_path('js/pages/PerformanceReports.vue'));

        $this->assertStringContainsString('ref="reportForm"', $page);
        $this->assertStringContainsString('reportForm.value?.checkValidity()', $page);
        $this->assertStringContainsString('reportForm.value?.reportValidity()', $page);
        $this->assertStringContainsString('if (!validateReportForm()) return;', $page);
        $this->assertStringContainsString('data-testid="pms-modal-error"', $page);
    }

    public function test_public_secure_report_has_readable_summary_metrics_and_discussion_sections(): void
    {
        $page = File::get(resource_path('js/pages/PublicReview.vue'));

        $this->assertStringContainsString('Ringkasan Performa', $page);
        $this->assertStringContainsString('primaryReportMetrics', $page);
        $this->assertStringContainsString('secondaryReportMetrics', $page);
        $this->assertStringContainsString('Pembahasan Laporan', $page);
        $this->assertStringContainsString('Dokumentasi Performa', $page);
        $this->assertStringContainsString('Tulis pertanyaan atau tanggapan...', $page);
        $this->assertStringContainsString('class="delivery-title', $page);
        $this->assertStringContainsString('class="media-caption', $page);
        $this->assertStringContainsString('color: #ffffff !important;', $page);
        $this->assertStringContainsString('background: linear-gradient(145deg, #ffffff 0%, #fffaf2 100%) !important;', $page);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $page);
        $this->assertStringContainsString('white-space: normal !important;', $page);
        $this->assertStringContainsString('white-space: pre-wrap !important;', $page);
    }

    public function test_public_secure_report_supports_persistent_local_light_and_dark_modes(): void
    {
        $layout = File::get(resource_path('js/layouts/PublicLayout.vue'));
        $toggle = File::get(resource_path('js/components/ThemeToggle.vue'));
        $page = File::get(resource_path('js/pages/PublicReview.vue'));

        $this->assertStringContainsString('<ThemeToggle', $layout);
        $this->assertStringContainsString('local-only', $layout);
        $this->assertStringContainsString('show-label', $layout);
        $this->assertStringContainsString('if (localOnly)', $toggle);
        $this->assertStringContainsString("themeStore.apply(themeStore.isDark ? 'light' : 'dark')", $toggle);
        $this->assertStringContainsString(":global(:root[data-theme='dark']) .delivery-page .review-card", $page);
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
