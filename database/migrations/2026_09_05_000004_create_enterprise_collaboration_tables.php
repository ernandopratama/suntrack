<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('attachable');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->nullable()->after('user_id')
                ->constrained('comments')->cascadeOnDelete();
            $table->dateTime('edited_at')->nullable()->after('body');
            $table->index(['commentable_type', 'commentable_id', 'created_at'], 'comment_thread_idx');
        });

        Schema::create('comment_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->unique(['comment_id', 'user_id']);
        });

        Schema::create('secure_link_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('secure_link_id')->constrained('secure_links')->cascadeOnDelete();
            $table->dateTime('accessed_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->string('visitor_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->index(['secure_link_id', 'accessed_at'], 'secure_link_access_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('next_reminder_at')->nullable();
            $table->dateTime('last_reminded_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->index(['progress_status', 'next_reminder_at'], 'task_reminder_due_idx');
        });

        $now = now();
        $settings = [
            ['key' => 'task_reminder_normal_minutes', 'value' => '1440', 'description' => 'Interval reminder Task priority normal dalam menit.'],
            ['key' => 'task_reminder_mid_minutes', 'value' => '480', 'description' => 'Interval reminder Task priority mid dalam menit.'],
            ['key' => 'task_reminder_urgent_minutes', 'value' => '120', 'description' => 'Interval reminder Task priority urgent dalam menit.'],
            ['key' => 'task_reminder_on_hold_minutes', 'value' => '1440', 'description' => 'Interval evaluasi Task on hold dalam menit.'],
        ];
        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + [
                    'type' => 'integer',
                    'group' => 'notification',
                    'is_public' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (DB::table('attachments')->exists()
            || DB::table('comment_reads')->exists()
            || DB::table('secure_link_access_logs')->exists()
            || DB::table('tasks')->where(function ($query) {
                $query->whereNotNull('next_reminder_at')
                    ->orWhereNotNull('last_reminded_at')
                    ->orWhere('reminder_count', '>', 0);
            })->exists()
            || DB::table('system_settings')->where(function ($query) {
                $query->where('key', 'task_reminder_normal_minutes')->where('value', '!=', '1440')
                    ->orWhere(function ($setting) {
                        $setting->where('key', 'task_reminder_mid_minutes')->where('value', '!=', '480');
                    })->orWhere(function ($setting) {
                        $setting->where('key', 'task_reminder_urgent_minutes')->where('value', '!=', '120');
                    })->orWhere(function ($setting) {
                        $setting->where('key', 'task_reminder_on_hold_minutes')->where('value', '!=', '1440');
                    });
            })->exists()) {
            throw new RuntimeException('Rollback ditolak: data kolaborasi Fase E harus diekspor atau dihapus secara eksplisit.');
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('task_reminder_due_idx');
            $table->dropColumn(['next_reminder_at', 'last_reminded_at', 'reminder_count']);
        });
        Schema::dropIfExists('secure_link_access_logs');
        Schema::dropIfExists('comment_reads');
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comment_thread_idx');
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'edited_at']);
        });
        Schema::dropIfExists('attachments');

        DB::table('system_settings')->whereIn('key', [
            'task_reminder_normal_minutes',
            'task_reminder_mid_minutes',
            'task_reminder_urgent_minutes',
            'task_reminder_on_hold_minutes',
        ])->delete();
    }
};
