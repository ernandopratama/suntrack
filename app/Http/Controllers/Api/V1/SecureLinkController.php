<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\ApprovalHistoryResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\SecureLinkResource;
use App\Models\Campaign;
use App\Models\Promotion;
use App\Models\SecureLink;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecureLinkController extends Controller
{
    // ==========================================
    // PROMOTION LINKS & DISCUSSIONS
    // ==========================================

    public function showPromotionLink(Promotion $promotion)
    {
        $link = $promotion->secureLinks()->first();
        return response()->json([
            'success' => true,
            'message' => 'Secure link retrieved successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    public function storePromotionLink(Request $request, Promotion $promotion)
    {
        $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $link = $promotion->secureLinks()->first();
        $isNew = false;

        if (!$link) {
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
        $link = $promotion->secureLinks()->first();
        if ($link && !$link->revoked_at) {
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
        return response()->json([
            'success' => true,
            'message' => 'Approval histories retrieved successfully.',
            'data' => ApprovalHistoryResource::collection($promotion->approvalHistories()->get()),
        ]);
    }

    public function storePromotionComment(StoreCommentRequest $request, Promotion $promotion)
    {
        $comment = $promotion->comments()->create([
            'user_id' => $request->user()->id,
            'author_name' => $request->user()->name,
            'author_position' => $request->author_position ?? 'Admin',
            'author_type' => 'Admin',
            'body' => $request->body,
        ]);

        ActivityLogger::log(
            'Comment Added',
            "Admin ({$request->user()->name}) commented: \"" . Str::limit($request->body, 60) . "\"",
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
        $link = $campaign->secureLinks()->first();
        return response()->json([
            'success' => true,
            'message' => 'Secure link retrieved successfully.',
            'data' => $link ? new SecureLinkResource($link) : null,
        ]);
    }

    public function storeCampaignLink(Request $request, Campaign $campaign)
    {
        $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $link = $campaign->secureLinks()->first();
        $isNew = false;

        if (!$link) {
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
        $link = $campaign->secureLinks()->first();
        if ($link && !$link->revoked_at) {
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
        $comment = $campaign->comments()->create([
            'user_id' => $request->user()->id,
            'author_name' => $request->user()->name,
            'author_position' => $request->author_position ?? 'Admin',
            'author_type' => 'Admin',
            'body' => $request->body,
        ]);

        ActivityLogger::log(
            'Comment Added',
            "Admin ({$request->user()->name}) commented: \"" . Str::limit($request->body, 60) . "\"",
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
}
