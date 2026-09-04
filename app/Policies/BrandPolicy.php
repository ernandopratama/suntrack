<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BrandPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'brand';
    }

    public function create(User $user): bool
    {
        return $this->dataScope->hasGlobalScope($user) && parent::create($user);
    }

    public function update(User $user, Model $brand): bool
    {
        return $this->dataScope->hasGlobalScope($user) && parent::update($user, $brand);
    }

    public function delete(User $user, Model $brand): bool
    {
        return $this->dataScope->hasGlobalScope($user) && parent::delete($user, $brand);
    }
}
