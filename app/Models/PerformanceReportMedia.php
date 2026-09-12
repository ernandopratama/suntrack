<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PerformanceReportMedia extends Model
{
    use HasUuids;

    protected $table = 'performance_report_media';

    protected $fillable = [
        'performance_report_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type',
        'size', 'title', 'notes', 'sort_order',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<PerformanceReport, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(PerformanceReport::class, 'performance_report_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected static function booted(): void
    {
        static::deleted(function (PerformanceReportMedia $media): void {
            Storage::disk($media->disk)->delete($media->path);
        });
    }
}
