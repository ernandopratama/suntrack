<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IdentifyReviewerRequest;
use App\Http\Requests\StoreApprovalRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PublicReviewResource;
use App\Http\Resources\TaskResource;
use App\Models\ApprovalHistory;
use App\Models\Campaign;
use App\Models\Promotion;
use App\Models\SecureLink;
use App\Models\Task;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicReviewController extends Controller
{
    /**
     * Validate token and return the link model or error response.
     */
    protected function getSecureLink(string $token)
    {
        $link = SecureLink::where('token', $token)->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'status' => 'Not Found',
                'message' => 'Tautan publik tidak ditemukan.',
                'code' => 404,
            ], 404);
        }

        if (in_array($link->status, ['Expired', 'Revoked'])) {
            return response()->json([
                'success' => false,
                'status' => $link->status,
                'message' => "Tautan ini telah {$link->status} dan tidak dapat diakses lagi.",
                'code' => 403,
            ], 403);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            $link->update(['status' => 'Expired']);

            return response()->json([
                'success' => false,
                'status' => 'Expired',
                'message' => 'Tautan publik telah kedaluwarsa.',
                'code' => 403,
            ], 403);
        }

        return $link;
    }

    private function linkable(SecureLink $link): Campaign|Promotion
    {
        $linkable = $link->linkable;

        if (! ($linkable instanceof Campaign) && ! ($linkable instanceof Promotion)) {
            abort(404);
        }

        return $linkable;
    }

    public function show(Request $request, string $token)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link; // Error response
        }

        $entity = $this->linkable($link);

        $link->increment('view_count');
        $link->update(['last_accessed_at' => now()]);

        // Audit log link opened (throttled in session or logged with IP/UA)
        ActivityLogger::log(
            'Secure Link Viewed',
            "Public review link opened from IP {$request->ip()}",
            'Brand',
            'Public Reviewer',
            $entity,
            null,
            null,
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'view_count' => $link->view_count,
            ]
        );

        // Load relationships based on linkable type:
        // - Promotion has: brand, campaign, variants, comments, activityLogs
        // - Campaign has: brand, comments, activityLogs (NOT campaign or variants)
        $linkableRelations = ['brand', 'comments', 'activityLogs'];
        if ($entity instanceof Promotion) {
            $linkableRelations = array_merge($linkableRelations, ['campaign', 'variants']);
        } else {
            // Campaign - load promotions with their variants, product info and campaign tasks for display
            $linkableRelations = array_merge($linkableRelations, ['promotions.variants.product', 'tasks']);
        }
        $entity->load($linkableRelations);

        return response()->json([
            'success' => true,
            'message' => 'Public review data retrieved successfully.',
            'data' => new PublicReviewResource($entity),
        ]);
    }

    public function identify(IdentifyReviewerRequest $request, string $token)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        $entity = $this->linkable($link);

        ActivityLogger::log(
            'Reviewer Identified',
            "Reviewer identified as {$request->reviewer_name} (".($request->reviewer_position ?? 'Reviewer').')'.($request->company_name ? " from {$request->company_name}" : ''),
            'Brand',
            $request->reviewer_name,
            $entity,
            null,
            $request->reviewer_position,
            [
                'company_name' => $request->company_name,
                'whatsapp_number' => $request->whatsapp_number,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Reviewer identity recorded successfully.',
        ]);
    }

    public function approveVariant(StoreApprovalRequest $request, string $token)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        $entity = $this->linkable($link);

        if ($entity instanceof Promotion) {
            $variant = $entity->variants()->where('variant_id', $request->variant_id)->first();
            if (! $variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant tidak ditemukan dalam promosi ini.',
                ], 404);
            }
            $promotions = collect([$entity]);
        } else {
            // Campaign - locate all promotions inside the campaign that contain this variant
            $campaign = $entity;
            $promotions = $campaign->promotions()
                ->whereHas('variants', fn ($q) => $q->where('variants.id', $request->variant_id))
                ->with(['variants' => fn ($q) => $q->where('variants.id', $request->variant_id)])
                ->get();

            if ($promotions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant tidak ditemukan dalam campaign ini.',
                ], 404);
            }
            $entity = $campaign;
        }

        $newStatus = $request->status;
        $oldStatus = $promotions->first()->variants->first()->pivot->approval_status ?? 'Pending';

        foreach ($promotions as $promotion) {
            $promotionVariant = $promotion->variants->first();

            // Update pivot
            $promotion->variants()->updateExistingPivot($promotionVariant->id, [
                'approval_status' => $newStatus,
                'rejection_notes' => $request->rejection_notes,
            ]);

            // Create immutable approval history
            ApprovalHistory::create([
                'promotion_id' => $promotion->id,
                'variant_id' => $promotionVariant->id,
                'reviewer_name' => $request->reviewer_name,
                'reviewer_position' => $request->reviewer_position ?? 'External Reviewer',
                'company_name' => $request->company_name,
                'whatsapp_number' => $request->whatsapp_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $request->rejection_notes,
            ]);

            // Dynamic status recalculation
            $promotion->recalculateApprovalStatus($request->reviewer_name, $request->reviewer_position ?? 'External Reviewer');
        }

        ActivityLogger::log(
            'Brand Approval',
            "Brand reviewer {$request->reviewer_name} {$newStatus} variant in campaign/promotion {$entity->name}.",
            'Brand',
            $request->reviewer_name,
            $entity,
            null,
            $request->reviewer_position,
            [
                'variant_id' => $request->variant_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'rejection_notes' => $request->rejection_notes,
                'company_name' => $request->company_name,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        if ($entity instanceof Promotion) {
            $entity->load(['brand', 'campaign', 'variants', 'comments', 'activityLogs']);
        } else {
            $entity->load(['brand', 'comments', 'activityLogs', 'promotions.variants.product', 'tasks']);
        }

        return response()->json([
            'success' => true,
            'message' => "Status approval variant berhasil diperbarui menjadi {$newStatus}.",
            'data' => new PublicReviewResource($entity),
        ]);
    }

    public function storeComment(StoreCommentRequest $request, string $token)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        $entity = $this->linkable($link);

        $comment = $entity->comments()->create([
            'author_name' => $request->author_name,
            'author_position' => $request->author_position ?? 'Reviewer',
            'author_type' => 'Brand',
            'body' => $request->body,
        ]);

        ActivityLogger::log(
            'Comment Added',
            "Brand reviewer {$request->author_name} commented: \"".Str::limit($request->body, 60).'"',
            'Brand',
            $request->author_name,
            $entity,
            null,
            $request->author_position,
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dikirim.',
            'data' => new CommentResource($comment),
        ]);
    }

    /**
     * Execute Batch Approval/Rejection on selected or all variants for external Brand reviewer (Sprint 8).
     */
    public function batchApproval(Request $request, string $token)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        $request->validate([
            'action' => ['required', Rule::in(['approve_selected', 'reject_selected', 'approve_all', 'reject_all'])],
            'variant_ids' => ['required_if:action,approve_selected,reject_selected', 'array'],
            'variant_ids.*' => ['uuid'],
            'rejection_notes' => ['required_if:action,reject_selected,reject_all', 'nullable', 'string', 'max:500'],
            'reviewer_name' => ['required', 'string', 'max:100'],
            'reviewer_position' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:100'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ]);

        $entity = $this->linkable($link);
        $action = $request->action;
        $targetStatus = str_starts_with($action, 'approve') ? 'Approved' : 'Rejected';
        $notes = $targetStatus === 'Rejected' ? $request->rejection_notes : null;

        if ($entity instanceof Promotion) {
            $promotions = collect([$entity]);
        } else {
            $promotions = $entity->promotions()->with('variants')->get();
        }

        $updatedCount = 0;
        foreach ($promotions as $promotion) {
            $variantsQuery = $promotion->variants();
            if (in_array($action, ['approve_selected', 'reject_selected'])) {
                $variantsQuery->whereIn('variants.id', $request->variant_ids);
            }
            $targetVariants = $variantsQuery->get();

            foreach ($targetVariants as $variant) {
                $oldStatus = $variant->pivot->approval_status ?? 'Pending';

                // Update pivot
                $promotion->variants()->updateExistingPivot($variant->id, [
                    'approval_status' => $targetStatus,
                    'rejection_notes' => $notes ?: ($variant->pivot->rejection_notes ?? null),
                ]);

                // Create immutable approval history
                ApprovalHistory::create([
                    'promotion_id' => $promotion->id,
                    'variant_id' => $variant->id,
                    'reviewer_name' => $request->reviewer_name,
                    'reviewer_position' => $request->reviewer_position ?? 'External Reviewer',
                    'company_name' => $request->company_name,
                    'whatsapp_number' => $request->whatsapp_number,
                    'old_status' => $oldStatus,
                    'new_status' => $targetStatus,
                    'notes' => $notes,
                ]);

                $updatedCount++;
            }

            if ($targetVariants->isNotEmpty()) {
                $promotion->recalculateApprovalStatus($request->reviewer_name, $request->reviewer_position ?? 'External Reviewer');
            }
        }

        if ($updatedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No matching variants found for batch operation.',
            ], 404);
        }

        ActivityLogger::log(
            'Brand Batch Approval Executed',
            "Brand Reviewer {$request->reviewer_name} executed [{$action}]: marked {$updatedCount} variants as {$targetStatus}.".($notes ? " (Note: {$notes})" : ''),
            'Brand',
            $request->reviewer_name,
            $entity,
            null,
            $request->reviewer_position,
            [
                'action' => $action,
                'target_status' => $targetStatus,
                'updated_count' => $updatedCount,
                'rejection_notes' => $notes,
                'company_name' => $request->company_name,
                'whatsapp_number' => $request->whatsapp_number,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        if ($entity instanceof Promotion) {
            $entity->load(['brand', 'campaign', 'variants', 'comments', 'activityLogs']);
        } else {
            $entity->load(['brand', 'comments', 'activityLogs', 'promotions.variants.product', 'tasks']);
        }

        return response()->json([
            'success' => true,
            'message' => "Batch approval Brand [{$action}] berhasil diproses untuk {$updatedCount} varian.",
            'data' => new PublicReviewResource($entity),
        ]);
    }

    /**
     * Update task progress status by external Brand reviewer via secure link.
     */
    public function updateTaskProgress(Request $request, string $token, Task $task)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        if ($link->linkable_type !== 'App\Models\Campaign' || $task->campaign_id !== $link->linkable_id) {
            return response()->json([
                'success' => false,
                'message' => 'Task tidak ditemukan pada campaign ini.',
            ], 404);
        }

        $request->validate([
            'progress_status' => ['required', 'string', Rule::in(['NotStarted', 'InProgress', 'Revision', 'Completed', 'OnHold'])],
        ]);

        // Prevent marking Completed when task requires visual but none present
        $desired = $request->progress_status;
        if ($desired === 'Completed' && $task->requires_visual) {
            $hasVisual = (! empty($task->visual_link) || ! empty($task->visual_file_path));
            if (! $hasVisual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task ini membutuhkan visual dan belum tersedia. Kirim visual terlebih dahulu sebelum menandai selesai.',
                ], 422);
            }
        }

        $oldStatus = $task->progress_status;
        $task->update(['progress_status' => $desired]);

        ActivityLogger::log(
            'Task Progress Updated',
            "Brand reviewer updated task '{$task->name}' status from {$oldStatus} to {$request->progress_status}.",
            'Brand',
            $request->input('reviewer_name', 'Brand Reviewer'),
            $task,
            null,
            $request->input('reviewer_position')
        );

        return response()->json([
            'success' => true,
            'message' => 'Status pengerjaan task berhasil diperbarui.',
            'data' => new TaskResource($task->fresh()),
        ]);
    }

    /**
     * Submit visual link or upload image for a visual-required task via secure link.
     */
    public function submitTaskVisual(Request $request, string $token, Task $task)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        if ($link->linkable_type !== 'App\Models\Campaign' || $task->campaign_id !== $link->linkable_id) {
            return response()->json([
                'success' => false,
                'message' => 'Task tidak ditemukan pada campaign ini.',
            ], 404);
        }

        $request->validate([
            'visual_link' => ['nullable', 'string', 'max:500'],
            'visual_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        if (! $request->filled('visual_link') && ! $request->hasFile('visual_file')) {
            return response()->json([
                'success' => false,
                'message' => 'Wajib mengisi Link Google Drive atau mengunggah gambar visual.',
            ], 422);
        }

        $data = [
            'visual_link' => $request->input('visual_link'),
            'submitted_by' => $request->input('reviewer_name', 'Brand Reviewer'),
            'submitted_at' => now(),
        ];

        if ($request->hasFile('visual_file')) {
            // delete previous file if exists
            if ($task->visual_file_path) {
                try {
                    if (Storage::disk('public')->exists($task->visual_file_path)) {
                        Storage::disk('public')->delete($task->visual_file_path);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete previous task visual: '.$e->getMessage(), ['task_id' => $task->id]);
                }
            }

            $file = $request->file('visual_file');
            $path = $file->store('task-visuals', 'public');
            $data['visual_file_path'] = $path;
            $data['visual_file_name'] = $file->getClientOriginalName();
        }

        // When a visual is submitted for a requires_visual task, mark it Completed
        if ($task->requires_visual) {
            $data['progress_status'] = 'Completed';
        }

        $task->update($data);

        ActivityLogger::log(
            'Task Visual Submitted',
            "Brand reviewer submitted visual for task '{$task->name}'".($request->input('visual_link') ? ' (link)' : ' (file)').'.',
            'Brand',
            $request->input('reviewer_name', 'Brand Reviewer'),
            $task,
            null,
            $request->input('reviewer_position'),
            [
                'visual_link' => $request->input('visual_link'),
                'visual_file_path' => $data['visual_file_path'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Visual task berhasil dikirim.',
            'data' => new TaskResource($task->fresh()),
        ]);
    }

    /**
     * Delete task visual (file & link) by external Brand reviewer via secure link.
     */
    public function deleteTaskVisual(Request $request, string $token, Task $task)
    {
        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        if ($link->linkable_type !== 'App\Models\Campaign' || $task->campaign_id !== $link->linkable_id) {
            return response()->json([
                'success' => false,
                'message' => 'Task tidak ditemukan pada campaign ini.',
            ], 404);
        }

        // delete file if exists
        if ($task->visual_file_path) {
            try {
                if (Storage::disk('public')->exists($task->visual_file_path)) {
                    Storage::disk('public')->delete($task->visual_file_path);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete task visual via API: '.$e->getMessage(), ['task_id' => $task->id]);
            }
        }

        $updateData = [
            'visual_file_path' => null,
            'visual_file_name' => null,
            'visual_link' => null,
            'submitted_by' => null,
            'submitted_at' => null,
        ];

        // If task requires visual, revert status to NotStarted when visual removed
        if ($task->requires_visual) {
            $updateData['progress_status'] = 'NotStarted';
        }

        $task->update($updateData);

        ActivityLogger::log(
            'Task Visual Deleted',
            "Brand reviewer removed visual for task '{$task->name}'.",
            'Brand',
            $request->input('reviewer_name', 'Brand Reviewer'),
            $task,
            null,
            $request->input('reviewer_position')
        );

        return response()->json([
            'success' => true,
            'message' => 'Visual task berhasil dihapus.',
            'data' => new TaskResource($task->fresh()),
        ]);
    }

    public function updateStatus(Request $request, string $token)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                'in:Draft,Active,Approved,Partially Approved,Rejected,Completed',
            ],
        ]);

        $link = $this->getSecureLink($token);
        if (! ($link instanceof SecureLink)) {
            return $link;
        }

        $entity = $this->linkable($link);
        $oldStatus = $entity->status;
        $newStatus = $request->status;

        $entity->update(['status' => $newStatus]);

        ActivityLogger::log(
            'Status Changed',
            "Status {$entity->name} diubah dari '{$oldStatus}' menjadi '{$newStatus}' oleh reviewer publik.",
            'Brand',
            'Public Reviewer',
            $entity,
            null,
            null,
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
            'status' => $entity->status,
        ]);
    }
}
