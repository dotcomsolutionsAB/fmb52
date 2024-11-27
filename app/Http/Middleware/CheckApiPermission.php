<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckApiPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $permission = null, $role = null)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($permission && !$user->can($permission)) {
            return response()->json(['message' => 'Permission denied.'], 403);
        }

        if ($role && !$user->hasRole($role)) {
            return response()->json(['message' => 'Role access denied.'], 403);
        }

        return $next($request);
    }
}
