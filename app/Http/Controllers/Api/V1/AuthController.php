<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
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
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

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
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                ]
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
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->success('User retrieved successfully.', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->permissions->pluck('name'),
            ]
        ]);
    }
}
