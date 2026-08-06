<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVariantRequest;
use App\Http\Requests\UpdateVariantRequest;
use App\Http\Resources\VariantResource;
use App\Models\Product;
use App\Models\Variant;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    use ApiResponse;

    /**
     * List all variants for a specific product.
     */
    public function index(Product $product, Request $request): JsonResponse
    {
        // Super Admin can access any variant
        if (!$request->user()->hasRole('Super Admin') && $product->brand->company_id !== $request->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $variants = $product->variants()
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate($request->get('per_page', 50));

        return $this->success('Variants retrieved successfully.', [
            'variants' => VariantResource::collection($variants)->response()->getData(true),
        ]);
    }

    public function store(StoreVariantRequest $request, Product $product): JsonResponse
    {
        // Super Admin can access any variant
        if (!$request->user()->hasRole('Super Admin') && $product->brand->company_id !== $request->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $variant = $product->variants()->create($request->validated());
        $user = $request->user();

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Variant '{$variant->code} - {$variant->name}' added to product '{$product->name}'.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $variant,
            actorId: $user->id
        );

        return $this->success('Variant created successfully.', [
            'variant' => new VariantResource($variant),
        ], 201);
    }

    public function update(UpdateVariantRequest $request, Product $product, Variant $variant): JsonResponse
    {
        // Super Admin can access any variant
        if (!$request->user()->hasRole('Super Admin') && $product->brand->company_id !== $request->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $variant->update($request->validated());
        $user = $request->user();

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Variant '{$variant->code} - {$variant->name}' was updated.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $variant,
            actorId: $user->id
        );

        return $this->success('Variant updated successfully.', [
            'variant' => new VariantResource($variant),
        ]);
    }

    public function destroy(Product $product, Variant $variant, Request $request): JsonResponse
    {
        // Super Admin can access any variant
        if (!$request->user()->hasRole('Super Admin') && $product->brand->company_id !== $request->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $user = $request->user();
        $codeName = "{$variant->code} - {$variant->name}";
        
        $variant->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Variant '{$codeName}' was removed from product '{$product->name}'.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $product,
            actorId: $user->id
        );

        return $this->success('Variant deleted successfully.');
    }
}
