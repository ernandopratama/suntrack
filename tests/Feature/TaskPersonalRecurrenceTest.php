<?php

namespace Tests\Feature;

use App\Jobs\GenerateRecurringTasksJob;
use App\Models\NotificationLog;
use App\Models\Task;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskPersonalRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherTeam;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-21 09:00:00');
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole(RbacRegistry::TEAM);
        $this->owner->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);

        $this->otherTeam = User::factory()->create();
        $this->otherTeam->assignRole(RbacRegistry::TEAM);
        $this->otherTeam->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RbacRegistry::ADMIN);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_can_create_a_personal_recurring_task_without_a_brand(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/admin/tasks', [
            'name' => 'Cek laporan pribadi',
            'description' => 'Periksa laporan harian sebelum rapat.',
            'is_personal' => true,
            'priority' => 'mid',
            'progress_status' => 'completed',
            'deadline' => now()->addDay()->toISOString(),
            'recurrence_type' => 'daily',
            'recurrence_ends_at' => now()->addWeek()->toISOString(),
            'recurrence_notify' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.task.is_personal', true)
            ->assertJsonPath('data.task.brand_id', null)
            ->assertJsonPath('data.task.campaign_id', null)
            ->assertJsonPath('data.task.pic_id', $this->owner->id)
            ->assertJsonPath('data.task.assignee_id', $this->owner->id)
            ->assertJsonPath('data.task.progress_status', 'assigned')
            ->assertJsonPath('data.task.recurrence_type', 'daily');

        $task = Task::query()->where('name', 'Cek laporan pribadi')->firstOrFail();
        $this->assertNotNull($task->next_recurrence_at);

        $this->actingAs($this->owner)->getJson('/api/v1/admin/tasks')
            ->assertOk()
            ->assertJsonPath('data.tasks.data.0.id', $task->id);

        $this->actingAs($this->otherTeam)->getJson("/api/v1/admin/tasks/{$task->id}")
            ->assertForbidden();

        $this->actingAs($this->admin)->getJson("/api/v1/admin/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_scheduler_generates_one_next_occurrence_and_notifies_the_owner(): void
    {
        $source = Task::query()->create([
            'name' => 'Rekap performa harian',
            'description' => 'Buat rekap performa.',
            'created_by' => $this->owner->id,
            'is_personal' => true,
            'pic_id' => $this->owner->id,
            'assignee_id' => $this->owner->id,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'deadline' => now()->subMinute(),
            'recurrence_type' => 'daily',
            'recurrence_ends_at' => now()->addDays(3),
            'next_recurrence_at' => now()->subMinute(),
            'recurrence_notify' => true,
        ]);

        app()->call([new GenerateRecurringTasksJob, 'handle']);

        $occurrence = Task::query()
            ->where('recurrence_source_id', $source->id)
            ->firstOrFail();

        $this->assertTrue($occurrence->is_personal);
        $this->assertSame($this->owner->id, $occurrence->assignee_id);
        $this->assertSame('assigned', $occurrence->progress_status);
        $this->assertNull($occurrence->recurrence_type);
        $this->assertSame(
            now()->subMinute()->addDay()->toDateTimeString(),
            $occurrence->deadline->toDateTimeString()
        );
        $this->assertDatabaseHas('notification_logs', [
            'recipient' => $this->owner->id,
            'notifiable_type' => Task::class,
            'notifiable_id' => $occurrence->id,
            'status' => 'sent',
        ]);

        app()->call([new GenerateRecurringTasksJob, 'handle']);
        $this->assertSame(1, Task::query()->where('recurrence_source_id', $source->id)->count());
        $this->assertSame(1, NotificationLog::query()->where('notifiable_id', $occurrence->id)->count());

        $this->actingAs($this->owner)->getJson('/api/v1/admin/tasks/notifications')
            ->assertOk()
            ->assertJsonPath('data.notifications.0.subject', 'Task Berulang Telah Dibuat');
    }
}
