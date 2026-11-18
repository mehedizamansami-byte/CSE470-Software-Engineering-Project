<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * CONTROLLER layer: JWT login for API clients (Feature 1).
 *
 * POST /api/login {email, password}  ->  {access_token, token_type, expires_in}
 * GET  /api/me    with "Authorization: Bearer <access_token>"
 */
class AuthController extends Controller
{
    private const TTL_MINUTES = 60;

    public function login(Request $request, JwtService $jwt): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        if (! $user->isVerified()) {
            return response()->json(['message' => 'Verify the account with its OTP code first.'], 403);
        }

        $user->forceFill(['last_login_at' => now(), 'deactivated_at' => null])->save();

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $jwt->issue($user, self::TTL_MINUTES),
            'expires_in' => self::TTL_MINUTES * 60,
            'user' => $this->present($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->present($request->user())]);
    }

    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $user->permissionList(),
            'ai_credits' => $user->workspaceOwner()?->ai_credits,
        ];
    }
}
