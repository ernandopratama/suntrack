<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Authorization\UserAccessService;
use App\Support\Rbac\RbacRegistry;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserRepository $repository,
        protected UserAccessService $userAccess,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $user = $request->user();

        $users = $this->repository->getFilteredPaginated(
            actor: $user,
            filters: $request->only(['search']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Users retrieved successfully.', [
            'users' => UserResource::collection($users)->response()->getData(true),
        ]);
    }

    public function accessOptions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $actor = $request->user();

        return $this->success('Access options retrieved successfully.', [
            'roles' => $actor->hasRole(RbacRegistry::SUPER_ADMIN)
                ? RbacRegistry::ROLES
                : [RbacRegistry::TEAM],
            'team_permissions' => RbacRegistry::TEAM_ALLOWED_PERMISSIONS,
            'companies' => Company::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'brands' => Brand::query()
                ->select(['id', 'company_id', 'name'])
                ->with('company:id,name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $authUser = $request->user();
        $data = $request->validated();
        $role = $data['role'] ?? RbacRegistry::TEAM;
        $hasPermissions = array_key_exists('permissions', $data);
        $hasCompanyScope = array_key_exists('company_ids', $data);
        $hasBrandScope = array_key_exists('brand_ids', $data);
        $permissions = $data['permissions'] ?? RbacRegistry::TEAM_DEFAULT_PERMISSIONS;
        $companyIds = $data['company_ids'] ?? [];
        $brandIds = $data['brand_ids'] ?? [];

        $this->authorize('assignRole', [User::class, $role]);

        if ($hasPermissions) {
            $this->authorize('assignPermissions', [User::class, $role]);
        }

        if ($hasCompanyScope || $hasBrandScope) {
            $this->authorize('assignScope', [User::class, $role]);
        }

        unset($data['role'], $data['permissions'], $data['company_ids'], $data['brand_ids']);
        $data['type'] = $role === RbacRegistry::TEAM ? 'team' : 'admin';

        // Hash password hanya jika diisi
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $newUser = DB::transaction(function () use (
            $data,
            $role,
            $permissions,
            $companyIds,
            $brandIds,
            $hasCompanyScope,
            $hasBrandScope,
            $authUser
        ) {
            $newUser = new User;
            $newUser->fill($data);
            $newUser->save();
            $newUser->syncRoles([$role]);

            if ($role === RbacRegistry::TEAM) {
                $newUser->syncPermissions($permissions);
                $this->syncScopeAssignments(
                    $newUser,
                    $authUser,
                    $hasCompanyScope ? $companyIds : null,
                    $hasBrandScope ? $brandIds : null
                );
            }

            $newUser->unsetRelations();
            $after = $this->userAccess->snapshot($newUser);
            $this->userAccess->logMutation(
                target: $newUser,
                before: null,
                after: $after,
                action: ActivityType::Created->value,
                description: "User '{$newUser->name}' was created with RBAC access.",
                actor: $authUser,
            );

            return $newUser;
        });

        $this->userAccess->invalidate($newUser);

        return $this->success('User created successfully.', [
            'user' => new UserResource($newUser->load('roles', 'permissions', 'assignedCompanies', 'assignedBrands')),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load([
            'roles',
            'permissions',
            'assignedCompanies',
            'assignedBrands',
            'activityLogs' => fn ($query) => $query->limit(20),
        ]);

        return $this->success('User retrieved successfully.', [
            'user' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $authUser = $request->user();
        $data = $request->validated();
        $role = $data['role'] ?? null;
        $targetRole = $role ?? $user->getRoleNames()->first();
        $hasPermissions = array_key_exists('permissions', $data);
        $hasCompanyScope = array_key_exists('company_ids', $data);
        $hasBrandScope = array_key_exists('brand_ids', $data);
        $permissions = $data['permissions'] ?? null;
        $companyIds = $data['company_ids'] ?? [];
        $brandIds = $data['brand_ids'] ?? [];

        if ($role !== null) {
            $this->authorize('assignRole', [User::class, $role]);

            if ($user->hasRole(RbacRegistry::SUPER_ADMIN)
                && $role !== RbacRegistry::SUPER_ADMIN
                && ! User::role(RbacRegistry::SUPER_ADMIN)->whereKeyNot($user->id)->exists()) {
                return $this->error('The last active Super Admin cannot be demoted.', [], 422);
            }

            $data['type'] = $role === RbacRegistry::TEAM ? 'team' : 'admin';
        }

        if ($hasPermissions) {
            $this->authorize('assignPermissions', [User::class, $targetRole]);
        }

        if ($hasCompanyScope || $hasBrandScope) {
            $this->authorize('assignScope', [User::class, $targetRole]);
        }

        unset($data['role'], $data['permissions'], $data['company_ids'], $data['brand_ids']);

        // Hash password jika ada
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use (
            $user,
            $data,
            $role,
            $targetRole,
            $permissions,
            $companyIds,
            $brandIds,
            $hasPermissions,
            $hasCompanyScope,
            $hasBrandScope,
            $authUser
        ) {
            $before = $this->userAccess->snapshot($user);
            $roleChanged = $role !== null && ! $user->hasRole($role);

            $user->update($data);

            if ($roleChanged) {
                $user->syncRoles([$role]);
                $user->syncPermissions(
                    $role === RbacRegistry::TEAM ? RbacRegistry::TEAM_DEFAULT_PERMISSIONS : []
                );
            }

            if ($targetRole === RbacRegistry::TEAM) {
                if ($hasPermissions) {
                    $user->syncPermissions($permissions);
                }

                $this->syncScopeAssignments(
                    $user,
                    $authUser,
                    $hasCompanyScope ? $companyIds : null,
                    $hasBrandScope ? $brandIds : null
                );
            } elseif ($roleChanged) {
                $this->syncScopeAssignments($user, $authUser, [], []);
            }

            $user->unsetRelations();
            $after = $this->userAccess->snapshot($user);
            $accessChanged = $before !== $after;

            $this->userAccess->logMutation(
                target: $user,
                before: $before,
                after: $after,
                action: $accessChanged
                    ? ActivityType::AccessUpdated->value
                    : ActivityType::Updated->value,
                description: $accessChanged
                    ? "RBAC access for user '{$user->name}' was updated."
                    : "User '{$user->name}' was updated.",
                actor: $authUser,
            );
        });

        $this->userAccess->invalidate($user);

        return $this->success('User updated successfully.', [
            'user' => new UserResource(
                $user->fresh()->load('roles', 'permissions', 'assignedCompanies', 'assignedBrands')
            ),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $authUser = request()->user();
        $userName = $user->name;
        DB::transaction(function () use ($user, $userName, $authUser) {
            $before = $this->userAccess->snapshot($user);
            $user->delete();
            $this->userAccess->logMutation(
                target: $user,
                before: $before,
                after: null,
                action: ActivityType::Deleted->value,
                description: "User '{$userName}' was deleted.",
                actor: $authUser,
            );
        });
        $this->userAccess->invalidate($user);

        return $this->success('User deleted successfully.');
    }

    /**
     * @param  array<int, string>|null  $companyIds
     * @param  array<int, string>|null  $brandIds
     */
    private function syncScopeAssignments(
        User $user,
        User $assignedBy,
        ?array $companyIds,
        ?array $brandIds
    ): void {
        if ($companyIds !== null) {
            $user->assignedCompanies()->syncWithPivotValues($companyIds, [
                'assigned_by' => $assignedBy->id,
            ]);
        }

        if ($brandIds !== null) {
            $user->assignedBrands()->syncWithPivotValues($brandIds, [
                'assigned_by' => $assignedBy->id,
            ]);
        }
    }
}
