<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerformanceReportMediaRequest;
use App\Http\Requests\UpdatePerformanceReportMediaRequest;
use App\Http\Resources\PerformanceReportMediaResource;
use App\Models\PerformanceReport;
use App\Models\PerformanceReportMedia;
use App\Models\SecureLink;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PerformanceReportMediaController extends Controller
{
    use ApiResponse;

    public function store(StorePerformanceReportMediaRequest $request, PerformanceReport $performanceReport): JsonResponse
    {
        $this->authorize('update', $performanceReport);
        $file = $request->file('image');
        $directory = 'performance-reports/'.$performanceReport->id;
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs($directory, $filename, 'local');
        if (! is_string($path)) {
            throw ValidationException::withMessages(['image' => 'Gambar gagal disimpan.']);
        }

        try {
            $media = $performanceReport->media()->create([
                'uploaded_by' => $request->user()->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'image/jpeg',
                'size' => $file->getSize(),
                'title' => $request->validated('title'),
                'notes' => $request->validated('notes'),
                'sort_order' => $request->integer('sort_order', 0),
            ]);
            $performanceReport->touch();
            $performanceReport->touch();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return $this->success('Gambar laporan berhasil ditambahkan.', [
            'media' => new PerformanceReportMediaResource($media),
        ], 201);
    }

    public function update(
        UpdatePerformanceReportMediaRequest $request,
        PerformanceReport $performanceReport,
        PerformanceReportMedia $media
    ): JsonResponse {
        $this->authorize('update', $performanceReport);
        $this->assertBelongsToReport($performanceReport, $media);
        $media->update($request->validated());
        $performanceReport->touch();
        $performanceReport->touch();

        return $this->success('Keterangan gambar berhasil diperbarui.', [
            'media' => new PerformanceReportMediaResource($media->fresh()),
        ]);
    }

    public function destroy(
        Request $request,
        PerformanceReport $performanceReport,
        PerformanceReportMedia $media
    ): JsonResponse {
        $this->authorize('update', $performanceReport);
        $this->assertBelongsToReport($performanceReport, $media);
        $media->delete();
        $performanceReport->touch();
        $performanceReport->touch();

        return $this->success('Gambar laporan berhasil dihapus.');
    }

    public function show(PerformanceReport $performanceReport, PerformanceReportMedia $media): StreamedResponse
    {
        $this->authorize('view', $performanceReport);
        $this->assertBelongsToReport($performanceReport, $media);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->response($media->path, $media->original_name, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function showPublic(string $token, PerformanceReportMedia $media): StreamedResponse
    {
        $link = SecureLink::query()->where('token', $token)->firstOrFail();
        abort_unless($link->isValid(), 403);
        abort_unless(
            $link->linkable_type === PerformanceReport::class
            && $link->linkable_id === $media->performance_report_id
            && $media->report()->where('status', 'published')->exists(),
            404
        );
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->response($media->path, $media->original_name, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function assertBelongsToReport(PerformanceReport $report, PerformanceReportMedia $media): void
    {
        abort_unless($media->performance_report_id === $report->id, 404);
    }
}
