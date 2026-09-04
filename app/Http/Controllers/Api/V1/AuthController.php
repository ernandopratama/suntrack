<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Authenticate an Admin user.
     */
    public function login(Request $request, DataScopeService $dataScope): JsonResponse
    {
        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:255', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:login'],
            'password' => ['required'],
        ]);

        $identifier = trim($data['login'] ?? $data['email']);
        $loginField = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $loginField === 'username' ? strtolower($identifier) : $identifier,
            'password' => $data['password'],
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            ActivityLogger::log(
                action: ActivityType::Login->value,
                description: 'Admin logged into the system.',
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $user,
                actorId: $user->id
            );

            return $this->success('Login successful.', [
                'user' => $this->accessProfile($user, $dataScope),
            ]);
        }

        return $this->error('The provided credentials do not match our records.', [], 401);
    }

    /**
     * Log out the current Admin user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            ActivityLogger::log(
                action: ActivityType::Logout->value,
                description: 'Admin logged out of the system.',
                actorType: 'Admin',
                actorName: $user->name,
                loggable: $user,
                actorId: $user->id
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success('Logout successful.');
    }

    /**
     * Get the authenticated user profile.
     */
    public function user(Request $request, DataScopeService $dataScope): JsonResponse
    {
        $user = $request->user();

        return $this->success('User retrieved successfully.', [
            'user' => $this->accessProfile($user, $dataScope),
        ]);
    }

    /** @return array<string, mixed> */
    private function accessProfile(User $user, DataScopeService $dataScope): array
    {
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        $roles = $user->getRoleNames()->values();
        $globalScope = $dataScope->hasGlobalScope($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $roles->first(),
            'roles' => $roles,
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'scope' => [
                'global' => $globalScope,
                'company_ids' => $globalScope ? [] : $dataScope->effectiveCompanyIds($user)->values(),
                'brand_ids' => $globalScope ? [] : $dataScope->effectiveBrandIds($user)->values(),
            ],
        ];
    }
}
