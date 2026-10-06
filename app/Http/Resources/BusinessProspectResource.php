<?php

namespace App\Http\Resources;

use App\Models\BusinessProspect;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessProspect */
class BusinessProspectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->statusEnum();
        $conversionStatus = $this->conversionStatusEnum();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'lead_temperature' => $this->lead_temperature,
            'city' => $this->city,
            'analysis_summary' => $this->analysis_summary,
            'analysis_link' => $this->analysis_link,
            'potential_reason' => $this->potential_reason,
            'instagram_url' => $this->instagram_url,
            'tiktok_url' => $this->tiktok_url,
            'facebook_or_website_url' => $this->facebook_or_website_url,
            'phone' => $this->phone,
            'pic_id' => $this->pic_id,
            'created_by' => $this->created_by,
            'analyzed_at' => $this->formatDate('analyzed_at', 'Y-m-d'),
            'status' => $status->value,
            'status_label' => $status->label(),
            'last_contact_at' => $this->formatDate('last_contact_at', 'Y-m-d\TH:i'),
            'next_follow_up_at' => $this->formatDate('next_follow_up_at', 'Y-m-d\TH:i'),
            'notes' => $this->notes,
            'lost_reason' => $this->lost_reason,
            'conversion_status' => $conversionStatus->value,
            'conversion_status_label' => $conversionStatus->label(),
            'conversion_rejection_reason' => $this->conversion_rejection_reason,
            'marketplace_links' => $this->whenLoaded('marketplaceLinks', fn () => $this->marketplaceLinks->map(fn ($link) => [
                'id' => $link->id,
                'marketplace' => $link->marketplace,
                'url' => $link->url,
            ])->values()),
            'pic' => $this->whenLoaded('pic', fn () => $this->pic ? [
                'id' => $this->pic->id,
                'name' => $this->pic->name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'converted_company' => $this->whenLoaded('convertedCompany', fn () => $this->convertedCompany ? [
                'id' => $this->convertedCompany->id,
                'name' => $this->convertedCompany->name,
            ] : null),
            'converted_brand' => $this->whenLoaded('convertedBrand', fn () => $this->convertedBrand ? [
                'id' => $this->convertedBrand->id,
                'name' => $this->convertedBrand->name,
            ] : null),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatDate(string $attribute, string $format): ?string
    {
        $value = $this->getRawOriginal($attribute);

        return $value ? CarbonImmutable::parse((string) $value)->format($format) : null;
    }
}
