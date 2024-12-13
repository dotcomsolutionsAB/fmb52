<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckApiPermission
{
    public function handle($request, Closure $next, $permission = null, $role = null)
    {
        $user = Auth::user();

        // If no permission or role is provided, skip checks
        if (!$permission && !$role) {
            return $next($request);
        }

        // Ensure the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Retrieve optional sector and sub-sector IDs
        $sectorId = $request->input('sector_id');
        $subSectorId = $request->input('sub_sector_id');

        // If sector or sub-sector IDs are required but missing
        if (($permission || $role) && (!$sectorId || !$subSectorId)) {
            return response()->json([
                'message' => 'Sector ID and Sub-Sector ID are required for permission or role validation.'
            ], 403);
        }

        // Check for permissions
        if ($permission) {
            $hasPermission = $user->permissions()
                ->where('name', $permission)
                ->when($sectorId, fn($query) => $query->whereRaw("FIND_IN_SET(?, sector_ids)", [$sectorId]))
                ->when($subSectorId, fn($query) => $query->whereRaw("FIND_IN_SET(?, sub_sector_ids)", [$subSectorId]))
                ->where(function ($query) {
                    $query->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('valid_to')
                          ->orWhere('valid_to', '>=', now());
                })
                ->exists();

            if (!$hasPermission) {
                return response()->json([
                    'message' => 'Permission denied or expired for this sector or sub-sector.'
                ], 403);
            }
        }

        // Check for roles
        if ($role) {
            $hasRole = $user->roles()
                ->where('name', $role)
                ->when($sectorId, fn($query) => $query->whereRaw("FIND_IN_SET(?, sector_ids)", [$sectorId]))
                ->when($subSectorId, fn($query) => $query->whereRaw("FIND_IN_SET(?, sub_sector_ids)", [$subSectorId]))
                ->where(function ($query) {
                    $query->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('valid_to')
                          ->orWhere('valid_to', '>=', now());
                })
                ->exists();

            if (!$hasRole) {
                return response()->json([
                    'message' => 'Role access denied or expired for this sector or sub-sector.'
                ], 403);
            }
        }

        return $next($request);
    }
}