<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionVariantRequest;
use App\Http\Resources\PromotionProductResource;
use App\Models\Promotion;
use App\Models\Variant;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionVariantController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DataScopeService $dataScope
    ) {}

    /**
     * List all variants mapped to this promotion, with promotion-specific pricing.
     */
    public function index(Promotion $promotion, Request $request): JsonResponse
    {
        $this->authorize('view', $promotion);

        $variants = $promotion->variants()
            ->with('product')
            ->orderBy('name')
            ->paginate($request->get('per_page', 50));

        return $this->success('Promotion variants retrieved successfully.', [
            'variants' => PromotionProductResource::collection($variants)->response()->getData(true),
        ]);
    }

    /**
     * Add or update a variant in this promotion.
     * On first addition, snapshots the master normal_price and bottom_price for history preservation.
     */
    public function store(PromotionVariantRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        // Validation is fully centralized in PromotionVariantRequest
        $variant = Variant::findOrFail($request->variant_id);
        if (! $this->dataScope->canAccess($request->user(), $variant) || $variant->product->brand_id !== $promotion->brand_id) {
            abort(404);
        }
        $user = $request->user();

        // Check if this variant is already in the promotion
        $alreadyMapped = $promotion->variants()->where('variant_id', $variant->id)->exists();

        $pivotData = [
            'id' => (string) Str::uuid(),
            'campaign_price' => $request->campaign_price,
            'bottom_price' => $request->bottom_price,
            'normal_price_snapshot' => $variant->normal_price, // Snapshot master price NOW
            'discount_price' => $request->discount_price,
            'promotion_stock' => $request->promotion_stock,
            'purchase_limit' => $request->purchase_limit,
            'notes' => $request->notes,
        ];

        if ($alreadyMapped) {
            $promotion->variants()->updateExistingPivot($variant->id, $pivotData);
            $action = 'Pricing Updated';
            $description = "Pricing updated for variant '{$variant->code}' in promotion '{$promotion->code}'.";
        } else {
            $promotion->variants()->attach($variant->id, $pivotData);
            $action = ActivityType::Created->value;
            $description = "Variant '{$variant->code} - {$variant->name}' was added to promotion '{$promotion->code}'.";
        }

        ActivityLogger::log(
            action: $action,
            description: $description,
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id,
            properties: $pivotData
        );

        // Return updated variant list
        $variants = $promotion->variants()->with('product')->orderBy('name')->paginate(50);

        return $this->success(
            $alreadyMapped ? 'Pricing updated successfully.' : 'Variant added to promotion successfully.',
            ['variants' => PromotionProductResource::collection($variants)->response()->getData(true)],
            $alreadyMapped ? 200 : 201
        );
    }

    /**
     * Remove a variant from the promotion.
     */
    public function destroy(Promotion $promotion, Variant $variant, Request $request): JsonResponse
    {
        $this->authorize('update', $promotion);
        if (! $this->dataScope->canAccess($request->user(), $variant)) {
            abort(404);
        }

        $promotion->variants()->detach($variant->id);
        $user = $request->user();

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Variant '{$variant->code}' was removed from promotion '{$promotion->code}'.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id
        );

        return $this->success('Variant removed from promotion successfully.');
    }
}
