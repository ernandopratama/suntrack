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
        'name',
        'progress_status',
        'requires_visual',
        'visual_type',
        'creative_brief',
        'deadline',
        'visual_link',
        'visual_file_path',
        'visual_file_name',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'requires_visual' => 'boolean',
        'creative_brief' => 'array',
        'deadline' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
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
