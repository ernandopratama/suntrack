<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        "brand_id", "name", "description", "start_date", "end_date", "status", "pic_id", "deadline", "notes"
    ];

    protected $casts = [
        "start_date" => "datetime",
        "end_date" => "datetime",
        "deadline" => "datetime",
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, "pic_id");
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function secureLinks()
    {
        return $this->morphMany(SecureLink::class, "linkable");
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable")->oldest();
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, "loggable")->latest();
    }
}

