<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['company_id', 'name'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }
}
