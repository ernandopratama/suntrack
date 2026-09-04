<?php

namespace App\Http\Resources;

use App\Models\ApprovalHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApprovalHistory */
class ApprovalHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'promotion_id' => $this->promotion_id,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant ? ($this->variant->product ? $this->variant->product->name.' - '.$this->variant->name : $this->variant->name) : 'Unknown Variant',
            'variant_sku' => $this->variant ? $this->variant->sku : null,
            'reviewer_name' => $this->reviewer_name,
            'reviewer_position' => $this->reviewer_position,
            'company_name' => $this->company_name,
            'whatsapp_number' => $this->whatsapp_number,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'notes' => $this->notes,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
