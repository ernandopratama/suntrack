<?php

namespace App\Http\Requests;

use App\Support\Rbac\RbacRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            && ($this->user()?->can('updatePermissions', $role) ?? false);
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $allowed = $role instanceof Role
            ? RbacRegistry::configurablePermissions($role->name)
            : [];

        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in($allowed)],
        ];
    }
}
