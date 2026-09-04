<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserBrandAssignment extends Pivot
{
    use HasUuids;

    public $incrementing = false;

    protected $table = 'user_brand_assignments';

    protected $fillable = [
        'user_id',
        'brand_id',
        'assigned_by',
    ];
}
