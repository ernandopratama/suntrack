<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Log a generic activity or an entity-specific activity.
     *
     * @param  string  $action  Example: 'Login', 'Campaign Created'
     * @param  string  $description  Detailed human-readable description
     * @param  string  $actorType  'Admin' or 'Brand'
     * @param  string  $actorName  Name of the person who did this
     * @param  Model|null  $loggable  The entity this action is related to (Campaign, Promotion, etc)
     * @param  string|null  $actorId  The User ID if actor is Admin
     * @param  string|null  $actorPosition  Position of the Brand user
     * @param  array|null  $properties  Old/New values or metadata
     */
    public static function log(
        string $action,
        string $description,
        string $actorType,
        string $actorName,
        ?Model $loggable = null,
        ?string $actorId = null,
        ?string $actorPosition = null,
        ?array $properties = null
    ): ActivityLog {

        $logData = [
            'action' => $action,
            'description' => $description,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'actor_position' => $actorPosition,
            'properties' => $properties,
        ];

        // If a model is provided, attach the log to it via polymorphism
        if ($loggable) {
            return $loggable->morphMany(ActivityLog::class, 'loggable')->create($logData);
        }

        // If no specific model is related (e.g., generic login), save without polymorphic relation.
        // We will mock the morphs by leaving them null (ensure DB allows this or use a dummy App\Models\System)
        // Wait, loggable_id and loggable_type are NOT null in DB.
        // We must bind generic actions to the User model itself.

        if ($actorType === 'Admin' && $actorId) {
            $user = User::find($actorId);
            if ($user) {
                return $user->activityLogs()->create($logData);
            }
        }

        // Fallback if somehow generic but no user
        return ActivityLog::create(array_merge($logData, [
            'loggable_type' => 'App\Models\User',
            'loggable_id' => $actorId ?? '00000000-0000-0000-0000-000000000000', // Mock fallback
        ]));
    }
}
