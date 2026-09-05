<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\ApprovalHistoryResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\SecureLinkAccessLogResource;
use App\Http\Resources\SecureLinkResource;
use App\Models\Campaign;
use App\Models\PerformanceReport;
use App\Models\Promotion;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Support\Rbac\RbacRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecureLinkController extends Controller
{
    public function showTaskLink(Request $request, Task $task): JsonResponse
    {
        return $this->showDeliveryLink($request, $task);
    }

    public function storeTaskLink(Request $request, Task $task): JsonResponse
    {
        if ($task->progress_status !== 'completed') {
            abort(422, 'Task must be completed before a Secure Link can be created.');
        }

        return $this->storeDeliveryLink($request, $task);
    }

    public function destroyTaskLink(Request $request, Task $task): JsonResponse
    {
        return $this->destroyDeliveryLink($request, $task);
    }

    public function taskAccessLogs(Request $request, Task $task): JsonResponse
    {
        return $this->accessLogs($request, $task);
    }

    public function showReportLink(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->showDeliveryLink($request, $performanceReport);
    }

    public function storeReportLink(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        if ($performanceReport->status !== 'published') {
            abort(422, 'Performance Report must be published before a Secure Link can be created.');
        }

        return $this->storeDeliveryLink($request, $performanceReport);
    }

    public function destroyReportLink(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->destroyDeliveryLink($request, $performanceReport);
    }

    public function reportAccessLogs(Request $request, PerformanceReport $performanceReport): JsonResponse
    {
        return $this->accessLogs($request, $performanceReport);
    }

    // ==========================================
    // PROMOTION LINKS & DISCUSSIONS
    // ==========================================

    public function showPromotionLink(Promotion $promotion)
    {
        $this->authorize('view', $promotion);

        $link = $promotion->secureLinks()->first();

        return response()->json([
            'success' => true,
            'message' => 'Secure link retrieved successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    public function storePromotionLink(Request $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $link = $promotion->secureLinks()->first();
        $isNew = false;

        if (! $link) {
            $link = $promotion->secureLinks()->create([
                'token' => Str::random(64),
                'expires_at' => $request->expires_at,
                'created_by' => $request->user()->id,
            ]);
            $isNew = true;
        } else {
            $link->update([
                'expires_at' => $request->expires_at,
                'revoked_at' => null, // Un-revoke if updating
            ]);
        }

        ActivityLogger::log(
            $isNew ? 'Public Link Created' : 'Public Link Updated',
            $isNew ? "Created secure public link for promotion {$promotion->code}" : "Updated expiration date for promotion {$promotion->code} public link",
            'Admin',
            $request->user()->name,
            $promotion,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => $isNew ? 'Secure link generated successfully.' : 'Secure link updated successfully.',
            'data' => new SecureLinkResource($link),
        ]);
    }

    public function regeneratePromotionLink(Request $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $link = $promotion->secureLinks()->first();
        if ($link) {
            $link->update([
                'token' => Str::random(64),
                'revoked_at' => null,
            ]);
        } else {
            $link = $promotion->secureLinks()->create([
                'token' => Str::random(64),
                'created_by' => $request->user()->id,
            ]);
        }

        ActivityLogger::log(
            'Public Link Regenerated',
            "Regenerated secure public link token for promotion {$promotion->code}",
            'Admin',
            $request->user()->name,
            $promotion,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Secure link regenerated successfully.',
            'data' => new SecureLinkResource($link),
        ]);
    }

    public function destroyPromotionLink(Request $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $link = $promotion->secureLinks()->first();
        if ($link && ! $link->revoked_at) {
            $link->update(['revoked_at' => now()]);

            ActivityLogger::log(
                'Public Link Revoked',
                "Revoked secure public link for promotion {$promotion->code}",
                'Admin',
                $request->user()->name,
                $promotion,
                $request->user()->id
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Secure link revoked successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    public function getPromotionHistories(Promotion $promotion)
    {
        $this->authorize('view', $promotion);

        return response()->json([
            'success' => true,
            'message' => 'Approval histories retrieved successfully.',
            'data' => ApprovalHistoryResource::collection($promotion->approvalHistories()->get()),
        ]);
    }

    public function storePromotionComment(StoreCommentRequest $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $comment = $promotion->comments()->create([
            'user_id' => $request->user()->id,
            'author_name' => $request->user()->name,
            'author_position' => $request->author_position ?? 'Admin',
            'author_type' => 'Admin',
            'body' => $request->body,
        ]);

        ActivityLogger::log(
            'Comment Added',
            "Admin ({$request->user()->name}) commented: \"".Str::limit($request->body, 60).'"',
            'Admin',
            $request->user()->name,
            $promotion,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => new CommentResource($comment),
        ]);
    }

    // ==========================================
    // CAMPAIGN LINKS & DISCUSSIONS
    // ==========================================

    public function showCampaignLink(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $link = $campaign->secureLinks()->first();

        return response()->json([
            'success' => true,
            'message' => 'Secure link retrieved successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    public function storeCampaignLink(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $link = $campaign->secureLinks()->first();
        $isNew = false;

        if (! $link) {
            $link = $campaign->secureLinks()->create([
                'token' => Str::random(64),
                'expires_at' => $request->expires_at,
                'created_by' => $request->user()->id,
            ]);
            $isNew = true;
        } else {
            $link->update([
                'expires_at' => $request->expires_at,
                'revoked_at' => null,
            ]);
        }

        ActivityLogger::log(
            $isNew ? 'Public Link Created' : 'Public Link Updated',
            $isNew ? "Created secure public link for campaign {$campaign->name}" : "Updated expiration date for campaign {$campaign->name} public link",
            'Admin',
            $request->user()->name,
            $campaign,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => $isNew ? 'Secure link generated successfully.' : 'Secure link updated successfully.',
            'data' => new SecureLinkResource($link),
        ]);
    }

    public function destroyCampaignLink(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $link = $campaign->secureLinks()->first();
        if ($link && ! $link->revoked_at) {
            $link->update(['revoked_at' => now()]);

            ActivityLogger::log(
                'Public Link Revoked',
                "Revoked secure public link for campaign {$campaign->name}",
                'Admin',
                $request->user()->name,
                $campaign,
                $request->user()->id
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Secure link revoked successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    public function storeCampaignComment(StoreCommentRequest $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $comment = $campaign->comments()->create([
            'user_id' => $request->user()->id,
            'author_name' => $request->user()->name,
            'author_position' => $request->author_position ?? 'Admin',
            'author_type' => 'Admin',
            'body' => $request->body,
        ]);

        ActivityLogger::log(
            'Comment Added',
            "Admin ({$request->user()->name}) commented: \"".Str::limit($request->body, 60).'"',
            'Admin',
            $request->user()->name,
            $campaign,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => new CommentResource($comment),
        ]);
    }

    private function showDeliveryLink(Request $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('view', $subject);
        $this->assertManager($request);
        $link = $subject->secureLinks()->with('creator')->first();

        return response()->json([
            'success' => true,
            'message' => 'Secure link retrieved successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    private function storeDeliveryLink(Request $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('update', $subject);
        $this->assertManager($request);
        $data = $request->validate(['expires_at' => ['nullable', 'date', 'after:now']]);
        $link = $subject->secureLinks()->first();
        $created = $link === null;
        if ($link === null) {
            $link = $subject->secureLinks()->create([
                'token' => Str::random(64),
                'expires_at' => $data['expires_at'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        } else {
            $link->update([
                'token' => Str::random(64),
                'expires_at' => $data['expires_at'] ?? null,
                'revoked_at' => null,
            ]);
        }

        ActivityLogger::log(
            $created ? 'Public Link Created' : 'Public Link Regenerated',
            ($created ? 'Created' : 'Regenerated').' Secure Link for '.class_basename($subject)." '{$this->subjectTitle($subject)}'.",
            'Admin',
            $request->user()->name,
            $subject,
            $request->user()->id,
            properties: ['secure_link_id' => $link->id, 'expires_at' => $link->expires_at?->toIso8601String()]
        );

        return response()->json([
            'success' => true,
            'message' => $created ? 'Secure link created successfully.' : 'Secure link regenerated successfully.',
            'data' => new SecureLinkResource($link->load('creator')),
        ], $created ? 201 : 200);
    }

    private function destroyDeliveryLink(Request $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('update', $subject);
        $this->assertManager($request);
        $link = $subject->secureLinks()->first();
        if ($link !== null && $link->revoked_at === null) {
            $link->update(['revoked_at' => now()]);
            ActivityLogger::log(
                'Public Link Revoked',
                'Revoked Secure Link for '.class_basename($subject)." '{$this->subjectTitle($subject)}'.",
                'Admin',
                $request->user()->name,
                $subject,
                $request->user()->id,
                properties: ['secure_link_id' => $link->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Secure link revoked successfully.',
            'data' => $link ? new SecureLinkResource($link->load('creator')) : null,
        ]);
    }

    private function accessLogs(Request $request, Task|PerformanceReport $subject): JsonResponse
    {
        $this->authorize('view', $subject);
        $this->assertManager($request);
        $link = $subject->secureLinks()->first();

        return response()->json([
            'success' => true,
            'message' => 'Secure link access logs retrieved successfully.',
            'data' => $link
                ? SecureLinkAccessLogResource::collection($link->accessLogs()->paginate(25))->response()->getData(true)
                : ['data' => []],
        ]);
    }

    private function assertManager(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN]), 403);
    }

    private function subjectTitle(Model $subject): string
    {
        return (string) ($subject->getAttribute('title') ?? $subject->getAttribute('name'));
    }
}
