<?php

namespace App\Http\Controllers\Api\V1;

use App\Repositories\AuditRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Enterprise Audit Dashboard Controller (Sprint 11).
 */
class AuditController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditRepository $audit = new AuditRepository
    ) {}

    /**
     * GET /api/v1/admin/audit/login-history
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:success,failed'],
            'ip_address' => ['sometimes', 'string'],
            'user_id' => ['sometimes', 'uuid'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $history = $this->audit->getLoginHistory(
            $request->only(['status', 'ip_address', 'user_id', 'date_from', 'date_to']),
            (int) $request->input('per_page', 20)
        );

        return $this->success('Login history retrieved.', ['login_history' => $history]);
    }

    /**
     * GET /api/v1/admin/audit/queue-history
     */
    public function queueHistory(Request $request): JsonResponse
    {
        $data = $this->audit->getQueueHistory($request->all());

        return $this->success('Queue history retrieved.', $data);
    }

    /**
     * GET /api/v1/admin/audit/error-logs
     */
    public function errorLogs(Request $request): JsonResponse
    {
        $lines = (int) $request->input('lines', 50);
        $data = $this->audit->getErrorLogs(min($lines, 200));

        return $this->success('Error logs retrieved.', $data);
    }

    /**
     * GET /api/v1/admin/audit/summary
     */
    public function summary(): JsonResponse
    {
        $data = $this->audit->getAuditSummary();

        return $this->success('Audit summary retrieved.', $data);
    }
}
