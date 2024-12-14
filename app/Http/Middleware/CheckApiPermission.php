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
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Retrieve all permissions assigned to the user
        $userPermissions = DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_has_permissions.model_id', $user->id)
            ->where('model_has_permissions.model_type', get_class($user))
            ->select('permissions.name')
            ->get()
            ->pluck('name')
            ->toArray();

        // Log or debug the permissions
        \Log::info('User Permissions:', $userPermissions);
        \Log::info('Required Permissions:', $permissions);

        // Check if the user has any of the specified permissions
        $hasPermission = false;

        if (!empty($permissions)) {
            $hasPermission = DB::table('model_has_permissions')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->where('model_has_permissions.model_id', $user->id)
                ->where('model_has_permissions.model_type', get_class($user))
                ->whereIn('permissions.name', $permissions)
                ->where(function ($query) {
                    $query->whereNull('model_has_permissions.valid_from')
                          ->orWhere('model_has_permissions.valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('model_has_permissions.valid_to')
                          ->orWhere('model_has_permissions.valid_to', '>=', now());
                })
                ->exists();
        }

        // Deny access if none of the permissions are valid
        if (!$hasPermission) {
            return response()->json([
                'message' => 'Access denied.',
                'user_permissions' => $userPermissions, // Print user's permissions for debugging
                'required_permissions' => $permissions, // Print required permissions for debugging
            ], 403);
        }

        return $next($request);
    }
}