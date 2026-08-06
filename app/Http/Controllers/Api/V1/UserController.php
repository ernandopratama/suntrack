<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->repository->getFilteredPaginated(
            companyId: (int) $request->user()->company_id,
            filters: $request->only(['search']),
            perPage: (int) $request->get('per_page', 15)
        );

        return $this->success('Users retrieved successfully.', [
            'users' => UserResource::collection($users)->response()->getData(true)
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        // Hash password hanya jika diisi
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $newUser = User::create($data);

        // Assign role: admin atau company
        if ($data['type'] === 'admin') {
            $newUser->assignRole('Admin');
        } else {
            $newUser->assignRole('Company');
        }

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "User '{$newUser->name}' ({$newUser->type}) was created.",
            actorType: 'Admin',
            actorName: $authUser->name,
            loggable: $newUser,
            actorId: $authUser->id
        );

        return $this->success('User created successfully.', [
            'user' => new UserResource($newUser)
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        if ($user->company_id !== request()->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $user->load('roles', 'permissions');

        return $this->success('User retrieved successfully.', [
            'user' => new UserResource($user)
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($user->company_id !== request()->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $authUser = $request->user();
        $data = $request->validated();

        // Hash password jika ada
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "User '{$user->name}' was updated.",
            actorType: 'Admin',
            actorName: $authUser->name,
            loggable: $user,
            actorId: $authUser->id
        );

        return $this->success('User updated successfully.', [
            'user' => new UserResource($user->fresh()->load('roles', 'permissions'))
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->company_id !== request()->user()->company_id) {
            return $this->error('Unauthorized.', [], 403);
        }

        $authUser = request()->user();
        $userName = $user->name;
        $user->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "User '{$userName}' was deleted.",
            actorType: 'Admin',
            actorName: $authUser->name,
            loggable: $user,
            actorId: $authUser->id
        );

        return $this->success('User deleted successfully.');
    }
}
