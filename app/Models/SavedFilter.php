<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'module', 'name', 'filters', 'is_default',
    ];

    protected $casts = [
        'filters'    => 'array',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
