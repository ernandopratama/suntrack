<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'action',
        'description',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_position',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Activity Logs are immutable, they only have created_at.
    const UPDATED_AT = null;

    /** @return MorphTo<Model, $this> */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
