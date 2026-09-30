<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectMarketplaceLink extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketplace',
        'url',
        'normalized_url_hash',
    ];

    /** @return BelongsTo<BusinessProspect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(BusinessProspect::class, 'business_prospect_id');
    }

    public static function normalizeUrl(string $url): string
    {
        return mb_strtolower(rtrim(trim($url), '/'));
    }

    public static function urlHash(string $url): string
    {
        return hash('sha256', self::normalizeUrl($url));
    }
}
