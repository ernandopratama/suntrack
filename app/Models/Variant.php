<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Variant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'code',
        'sku',
        'name',
        'normal_price',
        'bottom_price',
        'current_stock',
        'status',
    ];

    protected $casts = [
        'normal_price' => 'decimal:2',
        'bottom_price' => 'decimal:2',
        'current_stock' => 'integer',
    ];

    // =========================================================
    // Relationships
    // =========================================================

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Promotions this variant is mapped to, with full pivot pricing snapshot.
     */
    /** @return BelongsToMany<Promotion, $this> */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_variant')
            ->withPivot([
                'id',
                'campaign_price',
                'bottom_price',
                'normal_price_snapshot',
                'discount_price',
                'promotion_stock',
                'purchase_limit',
                'approval_status',
                'rejection_notes',
                'notes',
            ])
            ->withTimestamps();
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }
}
