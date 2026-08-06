<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecureLink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'linkable_type', 'linkable_id', 'token', 'expires_at', 'revoked_at', 'last_accessed_at', 'view_count', 'created_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'view_count' => 'integer',
    ];

    protected $appends = ['status'];

    public function linkable()
    {
        return $this->morphTo();
    }

    public function creator()
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
