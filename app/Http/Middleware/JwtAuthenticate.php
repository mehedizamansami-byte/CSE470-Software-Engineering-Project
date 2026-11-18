<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feature 1: authenticates API requests with "Authorization: Bearer <JWT>".
 */
class JwtAuthenticate
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $payload = $token ? $this->jwt->decode($token) : null;
        $user = $payload ? User::find($payload['sub'] ?? null) : null;

        if (! $user || ! $user->is_active || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthenticated. Send a valid token as "Authorization: Bearer <token>".'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
