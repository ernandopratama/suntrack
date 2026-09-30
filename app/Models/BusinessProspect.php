<?php

namespace App\Models;

use App\Enums\BusinessProspectStatus;
use App\Enums\ProspectConversionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessProspect extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'city',
        'analysis_summary',
        'analysis_link',
        'potential_reason',
        'instagram_url',
        'tiktok_url',
        'facebook_or_website_url',
        'phone',
        'pic_id',
        'created_by',
        'analyzed_at',
        'status',
        'last_contact_at',
        'next_follow_up_at',
        'notes',
        'lost_reason',
    ];

    protected function casts(): array
    {
        return [
            'analyzed_at' => 'date',
            'last_contact_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'conversion_requested_at' => 'datetime',
            'conversion_reviewed_at' => 'datetime',
            'status' => BusinessProspectStatus::class,
            'conversion_status' => ProspectConversionStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BusinessProspect $prospect): void {
            $prospect->name = Str::squish($prospect->name);
            $prospect->normalized_name = self::normalizeName($prospect->name);
        });
    }

    public static function normalizeName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    public function statusEnum(): BusinessProspectStatus
    {
        return BusinessProspectStatus::tryFrom((string) $this->getRawOriginal('status'))
            ?? BusinessProspectStatus::New;
    }

    public function conversionStatusEnum(): ProspectConversionStatus
    {
        return ProspectConversionStatus::tryFrom((string) $this->getRawOriginal('conversion_status'))
            ?? ProspectConversionStatus::None;
    }

    /** @return HasMany<ProspectMarketplaceLink, $this> */
    public function marketplaceLinks(): HasMany
    {
        return $this->hasMany(ProspectMarketplaceLink::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Company, $this> */
    public function convertedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'converted_company_id');
    }

    /** @return BelongsTo<Brand, $this> */
    public function convertedBrand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'converted_brand_id');
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }
}
