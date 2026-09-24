<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'promotion_id',
        'product_name',
        'variant_name',
        'normal_price',
        'discount_price',
        'discount_amount',
        'discount_percentage',
        'promotion_stock',
        'purchase_limit',
        'approval_status',
        'rejection_notes',
    ];

    protected $casts = [
        'normal_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:4',
        'promotion_stock' => 'integer',
        'purchase_limit' => 'integer',
    ];

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
