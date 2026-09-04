<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SecureLink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'linkable_type', 'linkable_id', 'token', 'expires_at', 'revoked_at', 'last_accessed_at', 'view_count', 'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'view_count' => 'integer',
    ];

    protected $appends = ['status'];

    /** @return MorphTo<Model, $this> */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at) {
            return 'Revoked';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }

    public function isValid(): bool
    {
        return $this->status === 'Active';
    }

    public function recordAccess(): void
    {
        $this->update([
            'last_accessed_at' => now(),
            'view_count' => $this->view_count + 1,
        ]);
    }
}
