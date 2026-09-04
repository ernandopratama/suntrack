<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Rbac\RbacRegistry;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('user.view') && $user->hasAnyRole([
            RbacRegistry::SUPER_ADMIN,
            RbacRegistry::ADMIN,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->can('user.update')) {
            return false;
        }

        if ($user->hasRole(RbacRegistry::SUPER_ADMIN)) {
            return true;
        }

        return $user->hasRole(RbacRegistry::ADMIN)
            && $user->isNot($target)
            && $target->hasRole(RbacRegistry::TEAM);
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->hasRole(RbacRegistry::SUPER_ADMIN) || ! $user->can('user.delete')) {
            return false;
        }

        if (! $target->hasRole(RbacRegistry::SUPER_ADMIN)) {
            return true;
        }

        return User::role(RbacRegistry::SUPER_ADMIN)->whereKeyNot($target->id)->exists();
    }

    public function assignRole(User $user, string $role): bool
    {
        if ($user->hasRole(RbacRegistry::SUPER_ADMIN)) {
            return in_array($role, RbacRegistry::ROLES, true);
        }

        return $user->hasRole(RbacRegistry::ADMIN) && $role === RbacRegistry::TEAM;
    }

    public function assignPermissions(User $user, string $targetRole): bool
    {
        return $targetRole === RbacRegistry::TEAM
            && $user->can('access.assign-permission')
            && $user->hasAnyRole([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN]);
    }

    public function assignScope(User $user, string $targetRole): bool
    {
        return $targetRole === RbacRegistry::TEAM
            && $user->can('access.assign-scope')
            && $user->hasAnyRole([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN]);
    }
}
