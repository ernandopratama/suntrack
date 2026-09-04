<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'default_landing_page', 'default_page_size',
        'theme', 'locale', 'timezone',
        'dashboard_widgets', 'extended',
    ];

    protected $casts = [
        'dashboard_widgets' => 'array',
        'extended' => 'array',
        'default_page_size' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
