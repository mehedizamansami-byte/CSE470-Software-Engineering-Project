<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feature 2: per-feature permission check.
 * Usage in routes: ->middleware('permission:content')  (any of several: 'permission:content,brand_kit')
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        foreach ($permissions as $permission) {
            if ($user && $user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to use this feature. Ask your shop owner for access.');
    }
}
