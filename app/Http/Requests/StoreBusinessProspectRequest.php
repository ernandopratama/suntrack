<?php

namespace App\Http\Requests;

use App\Enums\BusinessProspectStatus;
use App\Models\BusinessProspect;
use App\Models\ProspectMarketplaceLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreBusinessProspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'normalized_name' => ['required', 'string', Rule::unique('business_prospects', 'normalized_name')],
            'category' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'analysis_summary' => ['nullable', 'string'],
            'analysis_link' => ['nullable', 'url', 'max:2048'],
            'potential_reason' => ['nullable', 'string'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'tiktok_url' => ['nullable', 'url', 'max:2048'],
            'facebook_or_website_url' => ['nullable', 'url', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:50'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'analyzed_at' => ['nullable', 'date'],
            'status' => ['required', new Enum(BusinessProspectStatus::class)],
            'last_contact_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lost_reason' => ['nullable', 'string', 'required_if:status,not_interested,not_qualified'],
            'marketplace_links' => ['required', 'array', 'min:1'],
            'marketplace_links.*.marketplace' => ['required', 'string', 'max:50'],
            'marketplace_links.*.url' => ['required', 'url', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'normalized_name' => BusinessProspect::normalizeName((string) $this->input('name')),
        ]);
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $links = collect($this->input('marketplace_links', []));
            $duplicates = $links
                ->map(fn (array $link) => ProspectMarketplaceLink::normalizeUrl((string) ($link['url'] ?? '')))
                ->filter()
                ->duplicates();

            if ($duplicates->isNotEmpty()) {
                $validator->errors()->add('marketplace_links', 'Link marketplace yang sama tidak boleh ditambahkan dua kali.');
            }

            foreach ($links as $index => $link) {
                $hash = ProspectMarketplaceLink::urlHash((string) ($link['url'] ?? ''));
                if (ProspectMarketplaceLink::where('normalized_url_hash', $hash)->exists()) {
                    $validator->errors()->add("marketplace_links.{$index}.url", 'Link marketplace sudah digunakan oleh prospek lain.');
                }
            }
        }];
    }
}
