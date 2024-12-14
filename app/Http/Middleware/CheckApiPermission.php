<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckApiPermission
{
    public function handle($request, Closure $next, ...$permissions)
{
    $user = Auth::user();

    // Ensure the user is authenticated
    if (!$user) {
        return response()->json(['message' => 'Unauthorized.3e3e3'], 403);
    }

    // Retrieve all permissions assigned to the user
    $userPermissions = DB::table('model_has_permissions')
        ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
        ->where('model_has_permissions.model_id', $user->id)
        ->where('model_has_permissions.model_type', get_class($user))
        ->select('permissions.name')
        ->pluck('name')
        ->toArray();

    // Check if the user has at least one of the required permissions
    $hasPermission = !empty(array_intersect($permissions, $userPermissions));

    // Deny access if no required permissions match
    if (!$hasPermission) {
        return response()->json([
            'message' => 'Access denied.',
            'user_permissions' => $userPermissions, // Include user's permissions for debugging
            'required_permissions' => $permissions, // Include required permissions for debugging
        ], 403);
    }

    return $next($request);
}
}