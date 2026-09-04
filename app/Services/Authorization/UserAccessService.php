<?php

namespace App\Services\Authorization;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Spatie\Permission\PermissionRegistrar;

final class UserAccessService
{
    public function __construct(
        private readonly DataScopeService $dataScope,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /** @return array<string, array<int, string>> */
    public function snapshot(User $user): array
    {
        $user->loadMissing(['roles', 'permissions', 'assignedCompanies', 'assignedBrands']);

        return [
            'roles' => $user->roles->pluck('name')->sort()->values()->all(),
            'direct_permissions' => $user->permissions->pluck('name')->sort()->values()->all(),
            'company_ids' => $user->assignedCompanies->pluck('id')->sort()->values()->all(),
            'brand_ids' => $user->assignedBrands->pluck('id')->sort()->values()->all(),
        ];
    }

    public function invalidate(User $user): void
    {
        $this->permissionRegistrar->forgetCachedPermissions();
        $this->dataScope->forgetCachedScope($user);
        $user->unsetRelations();
    }

    /**
     * @param  array<string, array<int, string>>|null  $before
     * @param  array<string, array<int, string>>|null  $after
     */
    public function logMutation(
        User $target,
        ?array $before,
        ?array $after,
        string $action,
        string $description,
        ?User $actor = null,
        string $systemActorName = 'RBAC Migration',
    ): ActivityLog {
        return ActivityLogger::log(
            action: $action,
            description: $description,
            actorType: $actor === null ? 'System' : 'Admin',
            actorName: $actor === null ? $systemActorName : $actor->name,
            loggable: $target,
            actorId: $actor === null ? null : $actor->id,
            properties: [
                'target_user_id' => $target->id,
                'before' => $before,
                'after' => $after,
            ],
        );
    }
}
