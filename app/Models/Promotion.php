<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'campaign_id',
        'code',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    /**
     * Auto-generate a unique human-readable Promotion Code on creation.
     * Format: PRM-YYYYMM-XXXX (e.g., PRM-202607-0001)
     */
    protected static function booted(): void
    {
        static::creating(function (Promotion $promotion) {
            if (empty($promotion->code)) {
                $promotion->code = static::generateCode();
            }
        });
    }

    /**
     * Generate a sequential promotion code for the current month.
     */
    protected static function generateCode(): string
    {
        $prefix = 'PRM-' . now()->format('Ym') . '-';

        // Find the highest sequence number this month
        $lastCode = static::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $nextSequence = $lastCode
            ? (int) substr($lastCode, strlen($prefix)) + 1
            : 1;

        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================
    // Relationships
    // =========================================================

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Variants linked to this promotion via the promotion_variant pivot table.
     * This pivot already contains all pricing fields, anticipating Sprint 5.
     */
    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'promotion_variant')
            ->withPivot([
                'id',
                'campaign_price',
                'bottom_price',
                'discount_price',
                'promotion_stock',
                'purchase_limit',
                'normal_price_snapshot', // <--- Add this
                'approval_status',
                'rejection_notes',
            ])
            ->withTimestamps();
    }

    public function secureLinks()
    {
        return $this->morphMany(SecureLink::class, 'linkable');
    }

    public function approvalHistories()
    {
        return $this->hasMany(ApprovalHistory::class)->latest();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    /**
     * Dynamically compute and update parent Promotion status based on variant approval states.
     * Rule:
     * - All Approved -> Approved
     * - All Rejected -> Rejected
     * - All Pending (or 0 variants) -> Pending
     * - Mixed -> Partially Approved
     */
    public function recalculateApprovalStatus(?string $actorName = null, ?string $actorPosition = null): string
    {
        $variants = $this->variants()->get();
        $total = $variants->count();

        if ($total === 0) {
            $newStatus = 'Pending';
        } else {
            $approved = $variants->where('pivot.approval_status', 'Approved')->count();
            $rejected = $variants->where('pivot.approval_status', 'Rejected')->count();
            $pending  = $variants->where('pivot.approval_status', 'Pending')->count();

            if ($approved === $total) {
                $newStatus = 'Approved';
            } elseif ($rejected === $total) {
                $newStatus = 'Rejected';
            } elseif ($pending === $total) {
                $newStatus = 'Pending';
            } else {
                $newStatus = 'Partially Approved';
            }
        }

        if ($this->status !== $newStatus) {
            $oldStatus = $this->status;
            $this->update(['status' => $newStatus]);

            \App\Services\ActivityLogger::log(
                'Status Changed',
                "Promotion status automatically updated from '{$oldStatus}' to '{$newStatus}' based on Brand variant review.",
                'Brand',
                $actorName ?? 'Brand Reviewer',
                $this,
                null,
                $actorPosition
            );
        }

        return $newStatus;
    }
}
