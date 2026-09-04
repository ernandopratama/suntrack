<?php

namespace App\Models;

use App\Models\Pivots\UserCompanyAssignment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['name'];

    /** @return HasMany<Brand, $this> */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /** @return BelongsToMany<User, $this, UserCompanyAssignment, 'pivot'> */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company_assignments')
            ->using(UserCompanyAssignment::class)
            ->withPivot('id', 'assigned_by')
            ->withTimestamps();
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }
}
