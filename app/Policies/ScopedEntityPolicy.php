<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Model;

abstract class ScopedEntityPolicy
{
    public function __construct(
        protected DataScopeService $dataScope
    ) {}

    abstract protected function permissionPrefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.view')
            && $this->dataScope->canAccess($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.update')
            && $this->dataScope->canAccess($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.delete')
            && $this->dataScope->canAccess($user, $model);
    }
}
