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

        // Check permission validity
        if ($permission) {
            $hasValidPermission = $user->permissions()
                ->where('name', $permission)
                ->where(function ($query) {
                    $query->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('valid_to')
                          ->orWhere('valid_to', '>=', now());
                })
                ->exists();

            if (!$hasValidPermission) {
                return response()->json(['message' => 'Permission denied or expired.'], 403);
            }
        }

        // Check role validity
        if ($role) {
            $hasValidRole = $user->roles()
                ->where('name', $role)
                ->where(function ($query) {
                    $query->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('valid_to')
                          ->orWhere('valid_to', '>=', now());
                })
                ->exists();

            if (!$hasValidRole) {
                return response()->json(['message' => 'Role access denied or expired.'], 403);
            }
        }

        return $next($request);
    }
}