<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Search\GlobalSearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Global Search Controller (Sprint 11 — ADR-028).
 */
class SearchController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GlobalSearchService $search = new GlobalSearchService
    ) {}

    /**
     * Execute a global search across all entity types.
     *
     * GET /api/v1/admin/search?q={query}&types[]=campaign&types[]=promotion&limit=5
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'types' => ['sometimes', 'array'],
            'types.*' => ['string', 'in:campaign,promotion,product,variant,activity_log,comment'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $query = $request->string('q')->trim()->toString();
        $types = $request->array('types');
        $limit = (int) $request->input('limit', 5);

        $result = $this->search->search($query, $types, $limit);

        return $this->success('Search completed.', $result);
    }
}
