<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** @return HasMany<SecureLinkAccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(SecureLinkAccessLog::class)->latest('accessed_at');
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

    /** @param array<string, mixed> $context */
    public function recordAccess(array $context = []): void
    {
        $accessedAt = now();
        $this->increment('view_count');
        $this->forceFill(['last_accessed_at' => $accessedAt])->save();
        $this->accessLogs()->create([
            'accessed_at' => $accessedAt,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'referer' => $context['referer'] ?? null,
            'visitor_hash' => $context['visitor_hash'] ?? null,
            'metadata' => $context['metadata'] ?? null,
        ]);
    }
}
