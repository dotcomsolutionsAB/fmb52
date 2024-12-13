<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckApiPermission
{
    public function handle($request, Closure $next, $permission = null, $role = null)
    {
        $user = Auth::user();

        // Ensure the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Retrieve optional sector and sub-sector IDs
        $sectorId = $request->input('sector_id');
        $subSectorId = $request->input('sub_sector_id');

        $hasPermission = false;
        $hasRole = false;

        // Check for permissions
        if ($permission) {
            $hasPermission = DB::table('user_permission_sectors')
                ->join('permissions', 'user_permission_sectors.permission_id', '=', 'permissions.id')
                ->where('user_permission_sectors.user_id', $user->id)
                ->where('permissions.name', $permission)
                ->when($sectorId, fn($query) => $query->where('user_permission_sectors.sector_id', $sectorId))
                ->when($subSectorId, fn($query) => $query->where('user_permission_sectors.sub_sector_id', $subSectorId))
                ->where(function ($query) {
                    $query->whereNull('user_permission_sectors.valid_from')
                          ->orWhere('user_permission_sectors.valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('user_permission_sectors.valid_to')
                          ->orWhere('user_permission_sectors.valid_to', '>=', now());
                })
                ->exists();
        }

        // Check for roles
        if ($role) {
            $hasRole = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('role_id', function ($query) use ($role) {
                    $query->select('id')
                          ->from('roles')
                          ->where('name', $role)
                          ->limit(1);
                })
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
        }

        // Allow if either permission or role is valid
        if (!$hasPermission && !$hasRole) {
            return response()->json([
                'message' => 'Access denied for this sector or sub-sector.'
            ], 403);
        }

        return $next($request);
    }
}