<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CompanyPolicy extends ScopedEntityPolicy
{
    protected function permissionPrefix(): string
    {
        return 'company';
    }

    public function create(User $user): bool
    {
        return $this->dataScope->hasGlobalScope($user) && parent::create($user);
    }

    public function update(User $user, Model $company): bool
    {
        return $this->dataScope->hasGlobalScope($user) && parent::update($user, $company);
    }

    public function delete(User $user, Model $company): bool
    {
        return $this->dataScope->hasGlobalScope($user) && parent::delete($user, $company);
    }
}
