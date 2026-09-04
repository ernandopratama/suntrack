<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RbacRegistry::SUPER_ADMIN);
    }

    public function viewUsers(User $user, Role $role): bool
    {
        return $this->viewAny($user) && $this->isManagedRole($role);
    }

    public function updatePermissions(User $user, Role $role): bool
    {
        return $this->viewAny($user)
            && $this->isManagedRole($role)
            && RbacRegistry::isEditableRole($role->name);
    }

    private function isManagedRole(Role $role): bool
    {
        return $role->guard_name === 'web' && RbacRegistry::isFinalRole($role->name);
    }
}
