<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePromotionItemRequest;
use App\Http\Resources\PromotionItemResource;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Services\ActivityLogger;
use App\Services\PromotionItemSpreadsheetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromotionItemController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PromotionItemSpreadsheetService $spreadsheets
    ) {}

    public function index(Promotion $promotion): JsonResponse
    {
        $this->authorize('view', $promotion);

        return $this->success('Data produk promosi berhasil dimuat.', [
            'items' => PromotionItemResource::collection($this->orderedItems($promotion)),
        ]);
    }

    public function template(Promotion $promotion): BinaryFileResponse
    {
        $this->authorize('update', $promotion);

        $path = tempnam(sys_get_temp_dir(), 'promotion-template-');
        abort_if($path === false, 500, 'Tidak dapat membuat file template.');
        file_put_contents($path, $this->spreadsheets->template());

        return response()->download(
            $path,
            'template-data-promosi.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function preview(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        try {
            $inspection = $this->spreadsheets->inspect($validated['file']);
        } catch (\RuntimeException $exception) {
            return $this->error($exception->getMessage(), [], 422);
        }

        return $this->success('Preview import berhasil dibuat.', $inspection);
    }

    public function import(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        try {
            $inspection = $this->spreadsheets->inspect($validated['file']);
        } catch (\RuntimeException $exception) {
            return $this->error($exception->getMessage(), [], 422);
        }

        if (! $inspection['valid']) {
            return $this->error('File masih memiliki data yang tidak valid.', [
                'rows' => $inspection['rows'],
                'errors' => $inspection['errors'],
            ], 422);
        }

        DB::transaction(function () use ($promotion, $inspection): void {
            $promotion->promotionItems()->delete();
            $promotion->variants()->detach();

            foreach ($inspection['rows'] as $row) {
                $promotion->promotionItems()->create([
                    'product_name' => $row['product_name'],
                    'variant_name' => $row['variant_name'],
                    'normal_price' => $row['normal_price'],
                    'discount_price' => $row['discount_price'],
                    'discount_amount' => $row['discount_amount'],
                    'discount_percentage' => $row['discount_percentage'],
                    'promotion_stock' => $row['promotion_stock'],
                    'purchase_limit' => $row['purchase_limit'],
                ]);
            }
        });

        $user = $request->user();
        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Data produk promosi '{$promotion->code}' diganti melalui upload Excel ({$inspection['total_rows']} produk).",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id,
            properties: ['imported_rows' => $inspection['total_rows']]
        );

        return $this->success('Data produk promosi berhasil diimpor.', [
            'items' => PromotionItemResource::collection($this->orderedItems($promotion)),
            'imported' => $inspection['total_rows'],
        ]);
    }

    public function update(
        UpdatePromotionItemRequest $request,
        Promotion $promotion,
        PromotionItem $promotionItem
    ): JsonResponse {
        $this->authorize('update', $promotion);
        $this->ensureBelongsToPromotion($promotion, $promotionItem);

        $data = $request->validated();
        $normalPrice = (float) $data['normal_price'];
        $discountPrice = (float) $data['discount_price'];
        $discountAmount = $normalPrice - $discountPrice;

        $promotionItem->update([
            'product_name' => trim((string) $data['product_name']),
            'variant_name' => filled($data['variant_name'] ?? null) ? trim($data['variant_name']) : null,
            'normal_price' => $normalPrice,
            'discount_price' => $discountPrice,
            'promotion_stock' => $data['promotion_stock'] ?? null,
            'purchase_limit' => $data['purchase_limit'] ?? null,
            'discount_amount' => round($discountAmount, 2),
            'discount_percentage' => $normalPrice > 0
                ? round(($discountAmount / $normalPrice) * 100, 4)
                : 0,
        ]);

        $user = $request->user();
        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Produk '{$promotionItem->product_name}' pada promosi '{$promotion->code}' diperbarui.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id,
            properties: ['promotion_item_id' => $promotionItem->id]
        );

        return $this->success('Produk promosi berhasil diperbarui.', [
            'item' => new PromotionItemResource($promotionItem->refresh()),
            'items' => PromotionItemResource::collection($this->orderedItems($promotion)),
        ]);
    }

    public function destroy(Request $request, Promotion $promotion, PromotionItem $promotionItem): JsonResponse
    {
        $this->authorize('update', $promotion);
        $this->ensureBelongsToPromotion($promotion, $promotionItem);

        $productName = $promotionItem->product_name;
        $itemId = $promotionItem->id;
        $promotionItem->delete();

        $user = $request->user();
        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Produk '{$productName}' dihapus dari promosi '{$promotion->code}'.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $promotion,
            actorId: $user->id,
            properties: ['promotion_item_id' => $itemId]
        );

        return $this->success('Produk promosi berhasil dihapus.', [
            'items' => PromotionItemResource::collection($this->orderedItems($promotion)),
        ]);
    }

    private function ensureBelongsToPromotion(Promotion $promotion, PromotionItem $promotionItem): void
    {
        abort_unless($promotionItem->promotion_id === $promotion->id, 404);
    }

    private function orderedItems(Promotion $promotion)
    {
        return $promotion->promotionItems()
            ->orderByRaw('LOWER(product_name) ASC')
            ->orderByRaw("LOWER(COALESCE(variant_name, '')) ASC")
            ->get();
    }
}
