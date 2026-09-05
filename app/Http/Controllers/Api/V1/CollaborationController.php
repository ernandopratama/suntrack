<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\CommentResource;
use App\Models\Attachment;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\PerformanceReport;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\Notification\NotificationService;
use App\Support\Rbac\RbacRegistry;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollaborationController extends Controller
{
    use ApiResponse;

    public function __construct(private NotificationService $notifications) {}

    public function taskComments(Request $request, Task $task): JsonResponse
    {
        return $this->comments($request, $task);
    }

    public function storeTaskComment(StoreCommentRequest $request, Task $task): JsonResponse
    {
        return $this->storeComment($request, $task);
    }

    public function readTaskComments(Request $request, Task $task): JsonResponse
    {
        return $this->markRead($request, $task);
    }

    public function reportComments(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->comments($request, $performanceReport);
    }

    public function storeReportComment(StoreCommentRequest $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->storeComment($request, $performanceReport);
    }

    public function readReportComments(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->markRead($request, $performanceReport);
    }

    public function taskAttachments(Request $request, Task $task): JsonResponse
    {
        return $this->attachments($request, $task);
    }

    public function storeTaskAttachments(StoreAttachmentRequest $request, Task $task): JsonResponse
    {
        return $this->storeAttachments($request, $task);
    }

    public function reportAttachments(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->attachments($request, $performanceReport);
    }

    public function storeReportAttachments(StoreAttachmentRequest $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->storeAttachments($request, $performanceReport);
    }

    public function campaignAttachments(Request $request, Campaign $campaign): JsonResponse
    {
        return $this->attachments($request, $campaign);
    }

    public function storeCampaignAttachments(StoreAttachmentRequest $request, Campaign $campaign): JsonResponse
    {
        return $this->storeAttachments($request, $campaign);
    }

    public function downloadTaskAttachment(Task $task, Attachment $attachment): StreamedResponse
    {
        return $this->download($task, $attachment);
    }

    public function downloadReportAttachment(PerformanceReport $performanceReport, Attachment $attachment): StreamedResponse
    {
        return $this->download($performanceReport, $attachment);
    }

    public function downloadCampaignAttachment(Campaign $campaign, Attachment $attachment): StreamedResponse
    {
        return $this->download($campaign, $attachment);
    }

    public function destroyTaskAttachment(Request $request, Task $task, Attachment $attachment): JsonResponse
    {
        return $this->destroyAttachment($request, $task, $attachment);
    }

    public function destroyReportAttachment(Request $request, PerformanceReport $performanceReport, Attachment $attachment): JsonResponse
    {
        return $this->destroyAttachment($request, $performanceReport, $attachment);
    }

    public function destroyCampaignAttachment(Request $request, Campaign $campaign, Attachment $attachment): JsonResponse
    {
        return $this->destroyAttachment($request, $campaign, $attachment);
    }

    private function comments(Request $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('view', $subject);
        $comments = $subject->comments()->with(['attachments.uploader', 'readers'])->get();

        return $this->success('Discussion retrieved successfully.', [
            'comments' => CommentResource::collection($comments),
            'unread_count' => $comments->filter(fn (Comment $comment) => ! $comment->readers->contains($request->user()))->count(),
        ]);
    }

    private function storeComment(StoreCommentRequest $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('update', $subject);
        $data = $request->validated();
        $this->assertParentBelongsTo($data['parent_id'] ?? null, $subject);

        $comment = DB::transaction(function () use ($request, $subject, $data): Comment {
            $comment = $subject->comments()->create([
                'user_id' => $request->user()->id,
                'parent_id' => $data['parent_id'] ?? null,
                'author_name' => $request->user()->name,
                'author_position' => $request->user()->getRoleNames()->first(),
                'author_type' => $request->user()->hasAnyRole([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN]) ? 'Admin' : 'Tim',
                'body' => $data['body'],
            ]);
            $comment->readers()->attach($request->user()->id, ['read_at' => now()]);

            foreach ($request->file('attachments', []) as $file) {
                $this->persistAttachment($comment, $file, $request->user()->id);
            }

            return $comment;
        });

        ActivityLogger::log(
            'Discussion Message Added',
            "{$request->user()->name} added a message to ".class_basename($subject)." '{$this->subjectTitle($subject)}'.",
            $request->user()->hasRole(RbacRegistry::TEAM) ? 'Tim' : 'Admin',
            $request->user()->name,
            $subject,
            $request->user()->id,
            properties: ['comment_id' => $comment->id, 'parent_id' => $comment->parent_id]
        );

        $this->discussionRecipients($subject, $request->user()->id)->each(
            fn (string $recipient) => $this->notifications->send(
                'in_app',
                $recipient,
                $request->user()->name.' menambahkan pesan pada '.$this->subjectTitle($subject).'.',
                [
                    'subject' => 'Pesan Diskusi Baru',
                    'related_entity' => $subject::class,
                    'related_entity_id' => $subject->getKey(),
                    'event' => 'discussion.message.created',
                ]
            )
        );

        return $this->success('Message added successfully.', [
            'comment' => new CommentResource($comment->load(['attachments.uploader', 'readers'])),
        ], 201);
    }

    private function markRead(Request $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('view', $subject);
        $now = now();
        $rows = $subject->comments()->where('user_id', '!=', $request->user()->id)->pluck('id')
            ->map(fn (string $id) => ['comment_id' => $id, 'user_id' => $request->user()->id, 'read_at' => $now])
            ->all();
        if ($rows !== []) {
            DB::table('comment_reads')->upsert($rows, ['comment_id', 'user_id'], ['read_at']);
        }

        return $this->success('Discussion marked as read.');
    }

    private function attachments(Request $request, Campaign|Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        return $this->success('Attachments retrieved successfully.', [
            'attachments' => AttachmentResource::collection($subject->attachments()->with('uploader')->latest()->get()),
        ]);
    }

    private function storeAttachments(StoreAttachmentRequest $request, Campaign|Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('update', $subject);
        $attachments = collect();
        foreach ($request->file('files', []) as $file) {
            $attachments->push($this->persistAttachment($subject, $file, $request->user()->id));
        }

        ActivityLogger::log(
            'Attachments Added',
            $attachments->count().' attachment(s) added to '.class_basename($subject)." '{$this->subjectTitle($subject)}'.",
            $request->user()->hasRole(RbacRegistry::TEAM) ? 'Tim' : 'Admin',
            $request->user()->name,
            $subject,
            $request->user()->id,
            properties: ['attachment_ids' => $attachments->pluck('id')->all()]
        );

        $attachments->each(fn (Attachment $attachment) => $attachment->load('uploader'));

        return $this->success('Attachments uploaded successfully.', [
            'attachments' => AttachmentResource::collection($attachments),
        ], 201);
    }

    private function download(Campaign|Task|PerformanceReport $subject, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $subject);
        $direct = $attachment->attachable_type === $subject::class
            && $attachment->attachable_id === $subject->getKey();
        $commentAttachment = $attachment->attachable_type === Comment::class
            && $subject->comments()->whereKey($attachment->attachable_id)->exists();
        abort_unless($direct || $commentAttachment, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function destroyAttachment(Request $request, Campaign|Task|PerformanceReport $subject, Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $subject);
        $this->assertAttachmentBelongsTo($subject, $attachment);
        if ($attachment->uploaded_by !== $request->user()->id
            && ! $request->user()->hasAnyRole([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN])) {
            abort(403);
        }
        $attachment->delete();

        return $this->success('Attachment deleted successfully.');
    }

    private function persistAttachment(Campaign|Task|PerformanceReport|Comment $subject, UploadedFile $file, string $userId): Attachment
    {
        $disk = 'local';
        $directory = 'attachments/'.Str::kebab(class_basename($subject)).'/'.$subject->getKey();
        $filename = Str::uuid().($file->extension() ? '.'.$file->extension() : '');
        $path = $file->storeAs($directory, $filename, $disk);
        if (! is_string($path)) {
            throw ValidationException::withMessages(['files' => 'File could not be stored.']);
        }

        try {
            return $subject->attachments()->create([
                'uploaded_by' => $userId,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    private function assertParentBelongsTo(?string $parentId, Task|PerformanceReport $subject): void
    {
        if ($parentId === null) {
            return;
        }
        $exists = $subject->comments()->whereKey($parentId)->exists();
        if (! $exists) {
            throw ValidationException::withMessages(['parent_id' => 'Reply target must belong to the same discussion.']);
        }
    }

    private function assertAttachmentBelongsTo(Model $subject, Attachment $attachment): void
    {
        abort_unless(
            $attachment->attachable_type === $subject::class
            && $attachment->attachable_id === $subject->getKey(),
            404
        );
    }

    private function subjectTitle(Campaign|Task|PerformanceReport $subject): string
    {
        return $subject instanceof PerformanceReport ? $subject->title : $subject->name;
    }

    /** @return Collection<int, string> */
    private function discussionRecipients(Task|PerformanceReport $subject, string $senderId): Collection
    {
        $ids = $subject instanceof Task
            ? [$subject->pic_id, $subject->assignee_id]
            : [$subject->pic_id, $subject->author_id];

        return collect($ids)->filter(fn (?string $id) => $id !== null && $id !== $senderId)->unique()->values();
    }
}
