<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureLinkAccessLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'secure_link_id', 'accessed_at', 'ip_address', 'user_agent', 'referer', 'visitor_hash', 'metadata',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /** @return BelongsTo<SecureLink, $this> */
    public function secureLink(): BelongsTo
    {
        return $this->belongsTo(SecureLink::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Secure Link access logs are immutable.'));
        static::deleting(fn () => throw new \LogicException('Secure Link access logs are immutable.'));
    }
}
