<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Task extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'brand_id',
        'created_by',
        'pic_id',
        'assignee_id',
        'name',
        'description',
        'progress_status',
        'priority',
        'notes',
        'hold_reason',
        'revision_notes',
        'completion_summary',
        'completion_details',
        'completed_at',
        'requires_visual',
        'visual_type',
        'creative_brief',
        'deadline',
        'visual_link',
        'visual_file_path',
        'visual_file_name',
        'submitted_by',
        'submitted_at',
        'next_reminder_at',
        'last_reminded_at',
        'reminder_count',
    ];

    protected $casts = [
        'requires_visual' => 'boolean',
        'creative_brief' => 'array',
        'deadline' => 'datetime',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_reminder_at' => 'datetime',
        'last_reminded_at' => 'datetime',
        'reminder_count' => 'integer',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return MorphMany<SecureLink, $this> */
    public function secureLinks(): MorphMany
    {
        return $this->morphMany(SecureLink::class, 'linkable');
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    protected static function booted()
    {
        static::deleting(function ($task) {
            if ($task->visual_file_path) {
                try {
                    if (Storage::disk('public')->exists($task->visual_file_path)) {
                        Storage::disk('public')->delete($task->visual_file_path);
                    }
                } catch (\Exception $e) {
                    // don't block deletion: just log
                    Log::warning('Failed to delete task visual on model delete: '.$e->getMessage(), ['task_id' => $task->id]);
                }
            }
        });
    }
}
