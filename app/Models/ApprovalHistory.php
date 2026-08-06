<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'promotion_id',
        'variant_id',
        'reviewer_name',
        'reviewer_position',
        'company_name',
        'whatsapp_number',
        'old_status',
        'new_status',
        'notes',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
