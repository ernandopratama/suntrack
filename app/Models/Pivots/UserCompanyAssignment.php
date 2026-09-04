<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserCompanyAssignment extends Pivot
{
    use HasUuids;

    public $incrementing = false;

    protected $table = 'user_company_assignments';

    protected $fillable = [
        'user_id',
        'company_id',
        'assigned_by',
    ];
}
