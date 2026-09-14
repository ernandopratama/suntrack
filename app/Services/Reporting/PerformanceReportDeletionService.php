<?php

namespace App\Services\Reporting;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\PerformanceReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PerformanceReportDeletionService
{
    public function delete(PerformanceReport $report): void
    {
        $directories = DB::transaction(function () use ($report): array {
            $report = PerformanceReport::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            $media = $report->media()->get();
            $comments = $report->comments()->get();
            $commentIds = $comments->pluck('id');
            $attachments = $report->attachments()->get();
            $commentAttachments = Attachment::query()
                ->where('attachable_type', Comment::class)
                ->whereIn('attachable_id', $commentIds)
                ->get();

            $directories = collect()
                ->merge($media->map(fn ($item): array => [
                    'disk' => $item->disk,
                    'path' => 'performance-reports/'.$report->id,
                ]))
                ->merge($attachments->map(fn (Attachment $item): array => [
                    'disk' => $item->disk,
                    'path' => 'attachments/performance-report/'.$report->id,
                ]))
                ->merge($commentAttachments->map(fn (Attachment $item): array => [
                    'disk' => $item->disk,
                    'path' => 'attachments/comment/'.$item->attachable_id,
                ]))
                ->unique(fn (array $directory): string => $directory['disk'].'|'.$directory['path'])
                ->values()
                ->all();

            $media->each->delete();
            $attachments->each->delete();
            $commentAttachments->each->delete();
            $report->secureLinks()->delete();
            $report->comments()->delete();
            $report->activityLogs()->delete();
            $report->forceDelete();

            return $directories;
        });

        foreach ($directories as $directory) {
            Storage::disk($directory['disk'])->deleteDirectory($directory['path']);
        }
    }
}
