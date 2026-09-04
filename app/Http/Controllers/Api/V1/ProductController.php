<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Services\ProductImportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductRepository $repository,
        protected DataScopeService $dataScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $user = $request->user();

        $products = $this->repository->getFilteredPaginated(
            scope: $user,
            filters: $request->only(['search', 'status', 'brand_id']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Products retrieved successfully.', [
            'products' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $user = $request->user();

        // Verify brand belongs to user's company (Super Admin bypass)
        $brand = Brand::find($request->input('brand_id'));
        if (! $brand) {
            return $this->error('Brand not found.', [], 404);
        }
        if (! $this->dataScope->canAccess($user, $brand)) {
            abort(404);
        }

        $product = Product::create($request->validated());
        $product->load('brand');

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Product '{$product->code} - {$product->name}' was created.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $product,
            actorId: $user->id
        );

        return $this->success('Product created successfully.', [
            'product' => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product, Request $request): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['brand', 'variants']);

        return $this->success('Product retrieved successfully.', [
            'product' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        if ($request->filled('brand_id') && ! $this->dataScope->canAccessBrandId($request->user(), $request->input('brand_id'))) {
            abort(404);
        }

        $product->update($request->validated());

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Product '{$product->code} - {$product->name}' was updated.",
            actorType: 'Admin',
            actorName: $request->user()->name,
            loggable: $product,
            actorId: $request->user()->id
        );

        return $this->success('Product updated successfully.', [
            'product' => new ProductResource($product->fresh(['brand'])),
        ]);
    }

    public function destroy(Product $product, Request $request): JsonResponse
    {
        $this->authorize('delete', $product);

        $user = $request->user();
        $productName = $product->code.' - '.$product->name;

        $product->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Product '{$productName}' was deleted.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $product,
            actorId: $user->id
        );

        return $this->success('Product deleted successfully.');
    }

    /**
     * Import products from an Excel file.
     */
    public function import(ImportProductRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $brandId = $request->input('brand_id');
        $user = $request->user();

        // Verify brand belongs to user's company
        $brand = Brand::with('company')->find($brandId);

        if (! $brand) {
            return $this->error('Brand not found.', [], 404);
        }

        if (! $this->dataScope->canAccess($user, $brand)) {
            abort(404);
        }

        try {
            $service = new ProductImportService;
            $result = $service->import($file->getPathname(), $brandId, $user->id);

            $message = "Import completed: {$result['imported']} created, {$result['updated']} updated, {$result['skipped']} skipped.";
            if (! empty($result['errors'])) {
                $message .= ' Some rows had warnings.';
            }

            return $this->success($message, $result);
        } catch (\Exception $e) {
            return $this->error('Import failed: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Bulk delete products.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:products,id'],
        ]);

        $user = $request->user();
        $ids = $request->input('ids');
        $deleted = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $product = Product::find($id);
                if (! $product) {
                    continue;
                }

                // Check authorization
                if (! $this->dataScope->canAccess($user, $product)) {
                    $errors[] = "Product {$id}: Unauthorized.";

                    continue;
                }

                $product->delete();
                $deleted++;

                ActivityLogger::log(
                    action: ActivityType::Deleted->value,
                    description: "Product '".$product->code.' - '.$product->name."' was bulk deleted.",
                    actorType: 'Admin',
                    actorName: $user->name,
                    loggable: $product,
                    actorId: $user->id
                );
            } catch (\Exception $e) {
                $errors[] = "Product {$id}: ".$e->getMessage();
            }
        }

        $message = "{$deleted} product(s) deleted successfully.";
        if (! empty($errors)) {
            $message .= ' '.implode(' ', $errors);
        }

        return $this->success($message, [
            'deleted' => $deleted,
            'errors' => $errors,
        ]);
    }
}
