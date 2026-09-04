<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Pivots\UserBrandAssignment;
use App\Models\Pivots\UserCompanyAssignment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsToMany<Company, $this, UserCompanyAssignment, 'pivot'> */
    public function assignedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'user_company_assignments')
            ->using(UserCompanyAssignment::class)
            ->withPivot('id', 'assigned_by')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Brand, $this, UserBrandAssignment, 'pivot'> */
    public function assignedBrands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'user_brand_assignments')
            ->using(UserBrandAssignment::class)
            ->withPivot('id', 'assigned_by')
            ->withTimestamps();
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    /** @return HasMany<LoginHistory, $this> */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class)->latest('login_at');
    }
}
