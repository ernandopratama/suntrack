<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BrandRepository $repository
    ) {}

    /**
     * Display a listing of brands.
     */
    public function index(Request $request): JsonResponse
    {
        $brands = $this->repository->getFilteredPaginated(
            companyId: (int) $request->user()->company_id,
            filters: $request->only(['search']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Brands retrieved successfully.', [
            'brands' => BrandResource::collection($brands)->response()->getData(true)
        ]);
    }

    /**
     * Store a newly created brand.
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Auto-assign company_id from logged in user
        if (!isset($data['company_id'])) {
            $data['company_id'] = $user->company_id;
        }

        $brand = Brand::create($data);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Brand '{$brand->name}' was created.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $brand,
            actorId: $user->id
        );

        return $this->success('Brand created successfully.', [
            'brand' => new BrandResource($brand)
        ], 201);
    }

    /**
     * Display the specified brand.
     */
    public function show(Brand $brand): JsonResponse
    {
        if ($brand->company_id !== request()->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        return $this->success('Brand retrieved successfully.', [
            'brand' => new BrandResource($brand)
        ]);
    }

    /**
     * Update the specified brand.
     */
    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        if ($brand->company_id !== request()->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $user = $request->user();
        $brand->update($request->validated());

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Brand '{$brand->name}' was updated.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $brand,
            actorId: $user->id
        );

        return $this->success('Brand updated successfully.', [
            'brand' => new BrandResource($brand)
        ]);
    }

    /**
     * Remove the specified brand.
     */
    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->company_id !== request()->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $user = request()->user();
        $brandName = $brand->name;
        $brand->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Brand '{$brandName}' was deleted.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $brand,
            actorId: $user->id
        );

        return $this->success('Brand deleted successfully.');
    }
}
