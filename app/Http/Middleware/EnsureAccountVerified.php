<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feature 1: only active accounts that verified their OTP can use the app.
 * Usage in routes: ->middleware('verified.account')
 */
class EnsureAccountVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account has been suspended by an administrator.']);
        }

        if ($user && ! $user->isVerified()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Please verify your account with the OTP code first.'], 403)
                : redirect()->route('otp.show');
        }

        return $next($request);
    }
}
