<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\NotificationLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Notification Center Controller — 6-status delivery lifecycle (Sprint 11 ADR-029).
 */
class NotificationCenterController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/admin/notifications
     * List notifications with filters: type, status, date_from, date_to
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', 'in:whatsapp,email,in_app,sms'],
            'status' => ['sometimes', 'in:pending,processing,sent,delivered,failed,cancelled'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = NotificationLog::latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate((int) $request->input('per_page', 20));

        return $this->success('Notification history retrieved.', ['notifications' => $logs]);
    }

    /**
     * GET /api/v1/admin/notifications/{id}
     */
    public function show(NotificationLog $notification): JsonResponse
    {
        return $this->success('Notification detail retrieved.', ['notification' => $notification]);
    }

    /**
     * POST /api/v1/admin/notifications/{id}/retry
     */
    public function retry(NotificationLog $notification): JsonResponse
    {
        if (! $notification->canRetry()) {
            return $this->error('Notification cannot be retried.', [
                'reason' => 'Status is not failed or max_attempts reached.',
                'status' => $notification->status,
                'attempts' => $notification->attempts,
            ], 422);
        }

        // Reset to pending for the queue to pick up
        $notification->update([
            'status' => 'pending',
            'failure_reason' => null,
            'failed_at' => null,
        ]);
        $notification->incrementAttempts();

        return $this->success('Notification queued for retry.', ['notification' => $notification->fresh()]);
    }

    /**
     * POST /api/v1/admin/notifications/{id}/cancel
     */
    public function cancel(NotificationLog $notification): JsonResponse
    {
        if (! in_array($notification->status, ['pending', 'failed'])) {
            return $this->error('Cannot cancel a notification in its current state.', [
                'status' => $notification->status,
            ], 422);
        }

        $notification->markCancelled();

        return $this->success('Notification cancelled.', ['notification' => $notification->fresh()]);
    }

    /**
     * GET /api/v1/admin/notifications/summary
     * Return delivery statistics grouped by type and status.
     */
    public function summary(): JsonResponse
    {
        $breakdown = NotificationLog::selectRaw('type, status, COUNT(*) as count')
            ->groupBy('type', 'status')
            ->get()
            ->groupBy('type')
            ->map(fn ($rows) => $rows->mapWithKeys(fn (NotificationLog $log) => [
                $log->status => (int) $log->getAttribute('count'),
            ]));

        $totals = [
            'total' => NotificationLog::count(),
            'sent' => NotificationLog::where('status', 'sent')->orWhere('status', 'delivered')->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
            'pending' => NotificationLog::where('status', 'pending')->count(),
        ];

        return $this->success('Notification summary retrieved.', [
            'totals' => $totals,
            'breakdown' => $breakdown,
        ]);
    }
}
