<?php

namespace App\Services\Search\Drivers;

use App\Contracts\Search\SearchDriverInterface;
use App\Models\ActivityLog;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Variant;

/**
 * Default MySQL/SQLite LIKE-based search driver (ADR-028).
 * Provides relevance-ranked results with exact match prioritisation.
 */
class DatabaseSearchDriver implements SearchDriverInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function driverName(): string
    {
        return 'database';
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function search(string $query, array $types, int $limit, int|string|null $companyId): array
    {
        $results = [];
        $term = '%'.$query.'%';

        if (empty($types) || in_array('campaign', $types)) {
            $results['campaigns'] = Campaign::with('brand')
                ->when($companyId !== null, fn ($scope) => $scope->whereHas('brand', fn ($q) => $q->where('company_id', $companyId)))
                ->where('name', 'like', $term)
                ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$query])
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'type' => 'campaign',
                    'title' => $c->name,
                    'subtitle' => $c->brand->name ?? '',
                    'status' => $c->status,
                    'url' => "/campaigns/{$c->id}",
                ])->values()->all();
        }

        if (empty($types) || in_array('promotion', $types)) {
            $results['promotions'] = Promotion::with('brand')
                ->when($companyId !== null, fn ($scope) => $scope->whereHas('brand', fn ($q) => $q->where('company_id', $companyId)))
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)->orWhere('code', 'like', $term);
                })
                ->orderByRaw('CASE WHEN name = ? OR code = ? THEN 0 ELSE 1 END', [$query, $query])
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => 'promotion',
                    'title' => $p->code.' — '.$p->name,
                    'subtitle' => $p->brand->name ?? '',
                    'status' => $p->status,
                    'url' => "/promotions/{$p->id}",
                ])->values()->all();
        }

        if (empty($types) || in_array('product', $types)) {
            $results['products'] = Product::with('brand')
                ->when($companyId !== null, fn ($scope) => $scope->whereHas('brand', fn ($q) => $q->where('company_id', $companyId)))
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('code', 'like', $term);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => 'product',
                    'title' => $p->name,
                    'subtitle' => 'SKU: '.($p->sku ?? $p->code),
                    'status' => $p->status,
                    'url' => "/products/{$p->id}",
                ])->values()->all();
        }

        if (empty($types) || in_array('variant', $types)) {
            $results['variants'] = Variant::with('product.brand')
                ->when($companyId !== null, fn ($scope) => $scope->whereHas('product.brand', fn ($q) => $q->where('company_id', $companyId)))
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('code', 'like', $term);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'type' => 'variant',
                    'title' => $v->name,
                    'subtitle' => 'Product: '.($v->product->name ?? '').' · SKU: '.($v->sku ?? $v->code),
                    'status' => $v->status,
                    'url' => "/products/{$v->product_id}/variants/{$v->id}",
                ])->values()->all();
        }

        if (empty($types) || in_array('activity_log', $types)) {
            $results['activity_logs'] = ActivityLog::where('description', 'like', $term)
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'type' => 'activity_log',
                    'title' => $a->action.': '.mb_strimwidth($a->description, 0, 60, '...'),
                    'subtitle' => 'By '.$a->actor_name.' · '.$a->created_at?->diffForHumans(),
                    'status' => $a->action,
                    'url' => '/activity',
                ])->values()->all();
        }

        if (empty($types) || in_array('comment', $types)) {
            $results['comments'] = Comment::with('user')
                ->where('body', 'like', $term)
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'type' => 'comment',
                    'title' => mb_strimwidth($c->body, 0, 80, '...'),
                    'subtitle' => 'By '.($c->user->name ?? 'Brand').' · '.$c->created_at?->diffForHumans(),
                    'status' => 'comment',
                    'url' => "/comments/{$c->id}",
                ])->values()->all();
        }

        return $results;
    }
}
