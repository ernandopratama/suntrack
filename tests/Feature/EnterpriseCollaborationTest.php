<?php

namespace Tests\Feature;

use App\Jobs\SendTaskPriorityReminderJob;
use App\Models\Brand;
use App\Models\Company;
use App\Models\NotificationLog;
use App\Models\PerformanceReport;
use App\Models\SecureLinkAccessLog;
use App\Models\Task;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private Brand $outsideBrand;

    private User $admin;

    private User $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $company = Company::create(['name' => 'Collaboration Company']);
        $this->brand = Brand::create(['company_id' => $company->id, 'name' => 'Collaboration Brand']);
        $this->outsideBrand = Brand::create(['company_id' => $company->id, 'name' => 'Outside Brand']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(RbacRegistry::ADMIN);
        $this->team = User::factory()->create();
        $this->team->assignRole(RbacRegistry::TEAM);
        $this->team->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $this->team->assignedBrands()->attach($this->brand->id, ['assigned_by' => $this->admin->id]);
    }

    public function test_task_discussion_supports_reply_unread_and_private_attachments(): void
    {
        Storage::fake('local');
        $task = $this->task(['progress_status' => 'in_progress']);

        $response = $this->actingAs($this->admin)->post("/api/v1/admin/tasks/{$task->id}/comments", [
            'body' => 'Please revise the final caption.',
            'attachments' => [UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf')],
        ])->assertCreated();
        $commentId = $response->json('data.comment.id');
        $commentAttachmentId = $response->json('data.comment.attachments.0.id');

        $this->actingAs($this->team)->getJson("/api/v1/admin/tasks/{$task->id}/comments")
            ->assertOk()->assertJsonPath('data.unread_count', 1);
        $this->actingAs($this->team)->postJson("/api/v1/admin/tasks/{$task->id}/comments/read")
            ->assertOk();
        $this->actingAs($this->team)->postJson("/api/v1/admin/tasks/{$task->id}/comments", [
            'body' => 'Revision accepted.',
            'parent_id' => $commentId,
        ])->assertCreated()->assertJsonPath('data.comment.parent_id', $commentId);

        $upload = $this->actingAs($this->team)->post("/api/v1/admin/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->image('evidence.png')],
        ])->assertCreated();
        $attachmentId = $upload->json('data.attachments.0.id');
        $this->actingAs($this->team)->get("/api/v1/admin/tasks/{$task->id}/attachments/{$attachmentId}/download")
            ->assertOk();
        $this->actingAs($this->team)->get("/api/v1/admin/tasks/{$task->id}/attachments/{$commentAttachmentId}/download")
            ->assertOk();

        $this->assertDatabaseCount('comments', 2);
        $this->assertDatabaseCount('attachments', 2);
        $this->assertDatabaseHas('comment_reads', ['comment_id' => $commentId, 'user_id' => $this->team->id]);
    }

    public function test_task_and_report_secure_links_enforce_readiness_and_record_each_access(): void
    {
        Storage::fake('local');
        $task = $this->task(['progress_status' => 'in_progress']);
        $attachmentId = $this->actingAs($this->admin)->post("/api/v1/admin/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('delivery.pdf', 50, 'application/pdf')],
        ])->assertCreated()->json('data.attachments.0.id');
        $this->actingAs($this->admin)->postJson("/api/v1/admin/tasks/{$task->id}/secure-link")
            ->assertUnprocessable();
        $task->update(['progress_status' => 'completed', 'completed_at' => now()]);
        $taskLink = $this->actingAs($this->admin)->postJson("/api/v1/admin/tasks/{$task->id}/secure-link")
            ->assertCreated()->json('data');

        $this->getJson('/api/v1/public/review/'.$taskLink['token'])
            ->assertOk()->assertJsonPath('data.type', 'Task');
        $this->getJson('/api/v1/public/review/'.$taskLink['token'])->assertOk();
        $this->get("/api/v1/public/review/{$taskLink['token']}/attachments/{$attachmentId}/download")
            ->assertOk();
        $this->postJson('/api/v1/public/review/'.$taskLink['token'].'/comment', [
            'author_name' => 'Client Reviewer',
            'body' => 'Hasil sudah diterima.',
        ])->assertOk();
        $this->assertSame(2, SecureLinkAccessLog::where('secure_link_id', $taskLink['id'])->count());
        $this->assertDatabaseHas('comments', [
            'commentable_type' => Task::class,
            'commentable_id' => $task->id,
            'author_type' => 'Brand',
        ]);

        $report = $this->report(['status' => 'approved']);
        $this->actingAs($this->admin)->postJson("/api/v1/admin/performance-reports/{$report->id}/secure-link")
            ->assertUnprocessable();
        $report->update(['status' => 'published', 'published_at' => now()]);
        $reportLink = $this->actingAs($this->admin)->postJson("/api/v1/admin/performance-reports/{$report->id}/secure-link")
            ->assertCreated()->json('data');
        $this->getJson('/api/v1/public/review/'.$reportLink['token'])
            ->assertOk()->assertJsonPath('data.type', 'PerformanceReport');
        $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/performance-reports/{$report->id}/secure-link/access-logs")
            ->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_priority_reminder_is_deduplicated_and_logged(): void
    {
        $task = $this->task([
            'progress_status' => 'assigned',
            'priority' => 'urgent',
            'next_reminder_at' => now()->subMinute(),
        ]);

        app()->call([new SendTaskPriorityReminderJob, 'handle']);

        $task->refresh();
        $this->assertSame(1, $task->reminder_count);
        $this->assertTrue($task->next_reminder_at->isFuture());
        $this->assertSame(2, NotificationLog::where('notifiable_id', $task->id)->count());

        app()->call([new SendTaskPriorityReminderJob, 'handle']);
        $this->assertSame(2, NotificationLog::where('notifiable_id', $task->id)->count());
    }

    public function test_dashboard_task_and_report_kpis_follow_brand_scope(): void
    {
        $this->task(['priority' => 'urgent', 'deadline' => now()->subDay()]);
        $this->task(['brand_id' => $this->outsideBrand->id, 'progress_status' => 'completed']);
        $this->report(['status' => 'published']);
        $this->report(['brand_id' => $this->outsideBrand->id, 'status' => 'draft']);

        $this->actingAs($this->team)->getJson('/api/v1/admin/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.dashboard.kpi.tasks.total', 1)
            ->assertJsonPath('data.dashboard.kpi.tasks.urgent', 1)
            ->assertJsonPath('data.dashboard.kpi.tasks.overdue', 1)
            ->assertJsonPath('data.dashboard.kpi.performance_reports.total', 1)
            ->assertJsonPath('data.dashboard.kpi.performance_reports.published', 1);
    }

    public function test_contract_audit_requires_observation_and_backup_evidence(): void
    {
        $this->artisan('suntrack:contract-audit')->assertFailed()->expectsOutputToContain('CONTRACT_BLOCKED');

        $this->artisan('suntrack:contract-audit', [
            '--observation-start' => now()->subDays(8)->toIso8601String(),
            '--observation-end' => now()->subMinute()->toIso8601String(),
            '--backup-reference' => '/backup/suntrack.dump',
        ])->assertSuccessful()->expectsOutputToContain('CONTRACT_READY');
    }

    /** @param array<string, mixed> $overrides */
    private function task(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'brand_id' => $this->brand->id,
            'created_by' => $this->admin->id,
            'pic_id' => $this->admin->id,
            'assignee_id' => $this->team->id,
            'name' => 'Collaboration Task',
            'description' => 'Complete the assigned work.',
            'priority' => 'normal',
            'progress_status' => 'pending',
            'deadline' => now()->addDay(),
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function report(array $overrides = []): PerformanceReport
    {
        return PerformanceReport::create(array_merge([
            'brand_id' => $this->brand->id,
            'created_by' => $this->admin->id,
            'author_id' => $this->team->id,
            'pic_id' => $this->admin->id,
            'report_type' => 'daily',
            'title' => 'Collaboration Report',
            'period_start' => now()->toDateString(),
            'period_end' => now()->toDateString(),
            'executive_summary' => 'Summary',
            'content' => '<p>Content</p>',
            'status' => 'draft',
            'version' => 1,
        ], $overrides));
    }
}
