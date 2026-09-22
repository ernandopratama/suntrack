<?php

namespace Tests\Feature;

use App\Jobs\GenerateRecurringTasksJob;
use App\Jobs\SendTaskPriorityReminderJob;
use App\Models\NotificationLog;
use App\Models\Task;
use App\Models\User;
use App\Services\Workflow\TaskReminderService;
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
            'recurrence_interval' => 2,
            'recurrence_time' => '11:30',
            'recurrence_ends_at' => now()->addWeek()->toISOString(),
            'recurrence_max_occurrences' => 3,
            'recurrence_notify' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.task.is_personal', true)
            ->assertJsonPath('data.task.brand_id', null)
            ->assertJsonPath('data.task.campaign_id', null)
            ->assertJsonPath('data.task.pic_id', $this->owner->id)
            ->assertJsonPath('data.task.assignee_id', $this->owner->id)
            ->assertJsonPath('data.task.progress_status', 'assigned')
            ->assertJsonPath('data.task.recurrence_type', 'daily')
            ->assertJsonPath('data.task.recurrence_interval', 2)
            ->assertJsonPath('data.task.recurrence_time', '11:30')
            ->assertJsonPath('data.task.recurrence_max_occurrences', 3);

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

    public function test_owner_can_edit_a_personal_task_when_form_sends_null_brand(): void
    {
        $task = Task::query()->create([
            'name' => 'Task pribadi lama',
            'created_by' => $this->owner->id,
            'is_personal' => true,
            'pic_id' => $this->owner->id,
            'assignee_id' => $this->owner->id,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'deadline' => now()->addDays(2),
        ]);

        $this->actingAs($this->owner)->putJson("/api/v1/admin/tasks/{$task->id}", [
            'name' => 'Task pribadi diperbarui',
            'description' => 'Isi task setelah diperbarui.',
            'is_personal' => true,
            'brand_id' => null,
            'campaign_id' => null,
            'pic_id' => null,
            'assignee_id' => null,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'requires_visual' => false,
            'deadline' => now()->addDays(3)->toISOString(),
            'recurrence_type' => null,
            'recurrence_interval' => 1,
            'recurrence_time' => null,
            'recurrence_weekdays' => null,
            'recurrence_month_day' => null,
            'recurrence_ends_at' => null,
            'recurrence_max_occurrences' => null,
            'recurrence_notify' => true,
        ])->assertOk()
            ->assertJsonPath('data.task.name', 'Task pribadi diperbarui');
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

    public function test_weekly_schedule_uses_selected_day_time_and_occurrence_limit(): void
    {
        $source = Task::query()->create([
            'name' => 'Rekap mingguan terjadwal',
            'created_by' => $this->owner->id,
            'is_personal' => true,
            'pic_id' => $this->owner->id,
            'assignee_id' => $this->owner->id,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'deadline' => now()->subMinute(),
            'recurrence_type' => 'weekly',
            'recurrence_interval' => 1,
            'recurrence_time' => '14:30',
            'recurrence_weekdays' => [3, 5],
            'recurrence_max_occurrences' => 1,
            'next_recurrence_at' => now()->subMinute(),
            'recurrence_notify' => true,
        ]);

        app()->call([new GenerateRecurringTasksJob, 'handle']);

        $occurrence = Task::query()->where('recurrence_source_id', $source->id)->firstOrFail();
        $this->assertSame('2026-09-23 14:30:00', $occurrence->deadline->toDateTimeString());
        $this->assertSame(1, $source->refresh()->recurrence_generated_count);
        $this->assertNull($source->next_recurrence_at);
    }

    public function test_deadline_reminders_are_unread_until_user_closes_the_login_modal(): void
    {
        $task = Task::query()->create([
            'name' => 'Task pengingat login',
            'created_by' => $this->owner->id,
            'is_personal' => true,
            'pic_id' => $this->owner->id,
            'assignee_id' => $this->owner->id,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'deadline' => now()->addDay(),
            'recurrence_type' => 'daily',
            'next_reminder_at' => now()->subMinute(),
        ]);

        app()->call([new SendTaskPriorityReminderJob, 'handle']);

        $notification = NotificationLog::query()
            ->where('recipient', $this->owner->id)
            ->where('notifiable_id', $task->id)
            ->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($this->owner)->getJson('/api/v1/admin/tasks/notifications')
            ->assertOk()
            ->assertJsonPath('data.notifications.0.subject', 'Pengingat Task: Task pengingat login');

        $this->actingAs($this->owner)->postJson('/api/v1/admin/tasks/notifications/read')
            ->assertOk();

        $this->assertNotNull($notification->refresh()->read_at);
        $this->actingAs($this->owner)->getJson('/api/v1/admin/tasks/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data.notifications');
    }

    public function test_reminder_schedule_uses_daily_and_standard_offsets(): void
    {
        $daily = Task::query()->create([
            'name' => 'Task harian',
            'created_by' => $this->owner->id,
            'is_personal' => true,
            'pic_id' => $this->owner->id,
            'assignee_id' => $this->owner->id,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'deadline' => now()->addDays(2),
            'recurrence_type' => 'daily',
        ]);
        $standard = Task::query()->create([
            'name' => 'Task mingguan',
            'created_by' => $this->owner->id,
            'is_personal' => true,
            'pic_id' => $this->owner->id,
            'assignee_id' => $this->owner->id,
            'progress_status' => 'assigned',
            'priority' => 'normal',
            'deadline' => now()->addDays(7),
            'recurrence_type' => 'weekly',
        ]);

        $reminders = app(TaskReminderService::class);
        $reminders->schedule($daily);
        $reminders->schedule($standard);

        $this->assertSame(now()->addDay()->toDateTimeString(), $daily->refresh()->next_reminder_at->toDateTimeString());
        $this->assertSame(now()->addDays(4)->toDateTimeString(), $standard->refresh()->next_reminder_at->toDateTimeString());
    }
}
