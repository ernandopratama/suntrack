<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Rbac\RbacRegistry;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $rolesByName = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', RbacRegistry::ROLES)
            ->with('permissions:id,name')
            ->get()
            ->keyBy('name');

        $roles = collect(RbacRegistry::ROLES)
            ->map(fn (string $name) => $rolesByName->get($name))
            ->filter()
            ->map(fn (Role $role) => $this->rolePayload($role))
            ->values();

        return $this->success('Roles retrieved successfully.', [
            'roles' => $roles,
        ]);
    }

    public function users(Request $request, Role $role): JsonResponse
    {
        $this->authorize('viewUsers', $role);

        $search = trim((string) $request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $users = User::query()
            ->role($role->name)
            ->select(['users.id', 'users.name', 'users.username', 'users.email'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($scope) use ($search) {
                    $scope->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.username', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderBy('users.name')
            ->paginate($perPage);

        return $this->success('Role users retrieved successfully.', [
            'role' => ['id' => $role->id, 'name' => $role->name],
            'users' => [
                'data' => $users->getCollection()->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                ])->values(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function updatePermissions(
        UpdateRolePermissionsRequest $request,
        Role $role,
        PermissionRegistrar $permissionRegistrar,
    ): JsonResponse {
        $permissions = $request->validated('permissions');
        sort($permissions);
        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        DB::transaction(function () use ($role, $permissions) {
            foreach (RbacRegistry::GUARDS as $guard) {
                $guardRole = Role::findByName($role->name, $guard);
                $guardPermissions = Permission::query()
                    ->where('guard_name', $guard)
                    ->whereIn('name', $permissions)
                    ->get();

                $guardRole->syncPermissions($guardPermissions);
            }
        });

        $permissionRegistrar->forgetCachedPermissions();

        $actor = $request->user();
        ActivityLogger::log(
            action: ActivityType::AccessUpdated->value,
            description: "Permissions for role '{$role->name}' were updated.",
            actorType: 'Admin',
            actorName: $actor->name,
            loggable: $actor,
            actorId: $actor->id,
            properties: [
                'target_role' => $role->name,
                'before' => $before,
                'after' => $permissions,
                'guards' => RbacRegistry::GUARDS,
            ],
        );

        $role->load('permissions:id,name');

        return $this->success('Role permissions updated successfully.', [
            'role' => $this->rolePayload($role),
        ]);
    }

    /** @return array<string, mixed> */
    private function rolePayload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->sort()->values(),
            'allowed_permissions' => RbacRegistry::configurablePermissions($role->name),
            'users_count' => User::role($role->name)->count(),
            'editable' => RbacRegistry::isEditableRole($role->name),
        ];
    }
}
