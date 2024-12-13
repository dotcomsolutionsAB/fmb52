<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckApiPermission
{
    // public function handle($request, Closure $next, $permission = null, $role = null)
    // {
    //     $user = Auth::user();

    //     // If no permission or role is provided, skip checks
    //     if (!$permission && !$role) {
    //         return $next($request);
    //     }

    //     // Ensure the user is authenticated
    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthorized.'], 403);
    //     }

    //     // Retrieve optional sector and sub-sector IDs
    //     $sectorId = $request->input('sector_id');
    //     $subSectorId = $request->input('sub_sector_id');

    //     // If sector or sub-sector IDs are required but missing
    //     if (($permission || $role) && (!$sectorId && !$subSectorId)) {
    //         return response()->json([
    //             'message' => 'Sector ID and Sub-Sector ID are required for permission or role validation.'
    //         ], 403);
    //     }

    //     // Check for permissions
    //     if ($permission) {
    //         $hasPermission = DB::table('user_permission_sectors')
    //             ->where('user_id', $user->id)
    //             ->where('permission_id', function ($query) use ($permission) {
    //                 $query->select('id')
    //                       ->from('permissions')
    //                       ->where('name', $permission)
    //                       ->limit(1);
    //             })
    //             ->when($sectorId, fn($query) => $query->where('sector_id', $sectorId))
    //             ->where(function ($query) {
    //                 $query->whereNull('valid_from')
    //                       ->orWhere('valid_from', '<=', now());
    //             })
    //             ->where(function ($query) {
    //                 $query->whereNull('valid_to')
    //                       ->orWhere('valid_to', '>=', now());
    //             })
    //             ->exists();

    //         if ($subSectorId) {
    //             $hasPermission = $hasPermission || DB::table('user_permission_sub_sectors')
    //                 ->where('user_id', $user->id)
    //                 ->where('permission_id', function ($query) use ($permission) {
    //                     $query->select('id')
    //                           ->from('permissions')
    //                           ->where('name', $permission)
    //                           ->limit(1);
    //                 })
    //                 ->where('sub_sector_id', $subSectorId)
    //                 ->where(function ($query) {
    //                     $query->whereNull('valid_from')
    //                           ->orWhere('valid_from', '<=', now());
    //                 })
    //                 ->where(function ($query) {
    //                     $query->whereNull('valid_to')
    //                           ->orWhere('valid_to', '>=', now());
    //                 })
    //                 ->exists();
    //         }

    //         if (!$hasPermission) {
    //             return response()->json([
    //                 'message' => 'Permission denied or expired for this sector or sub-sector.'
    //             ], 403);
    //         }
    //     }

    //     // Check for roles
    //     if ($role) {
    //         $hasRole = DB::table('model_has_roles')
    //             ->where('model_id', $user->id)
    //             ->where('role_id', function ($query) use ($role) {
    //                 $query->select('id')
    //                       ->from('roles')
    //                       ->where('name', $role)
    //                       ->limit(1);
    //             })
    //             ->when($sectorId, fn($query) => $query->whereRaw("FIND_IN_SET(?, sector_ids)", [$sectorId]))
    //             ->when($subSectorId, fn($query) => $query->whereRaw("FIND_IN_SET(?, sub_sector_ids)", [$subSectorId]))
    //             ->where(function ($query) {
    //                 $query->whereNull('valid_from')
    //                       ->orWhere('valid_from', '<=', now());
    //             })
    //             ->where(function ($query) {
    //                 $query->whereNull('valid_to')
    //                       ->orWhere('valid_to', '>=', now());
    //             })
    //             ->exists();

    //         if (!$hasRole) {
    //             return response()->json([
    //                 'message' => 'Role access denied or expired for this sector or sub-sector.'
    //             ], 403);
    //         }
    //     }

    //     return $next($request);
    // }


    public function handle($request, Closure $next, $permission = null, $role = null)
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    
        $sectorId = $request->input('sector_id');
        $subSectorId = $request->input('sub_sector_id');
    
        // Case 1: No sector_id and sub_sector_id provided
        if (!$sectorId && !$subSectorId) {
            $userPermissions = \DB::table('user_permission_sectors')
                ->join('permissions', 'user_permission_sectors.permission_id', '=', 'permissions.id')
                ->where('user_permission_sectors.user_id', $user->id)
                ->when($permission, function ($query) use ($permission) {
                    $query->where('permissions.name', $permission);
                })
                ->select('user_permission_sectors.*', 'permissions.name as permission_name')
                ->get();
    
            if ($userPermissions->isEmpty()) {
                return response()->json([
                    'message' => "You don't have permission.",
                    'debug' => [
                        'checked_permission' => $permission,
                        'sector_id' => $sectorId,
                        'sub_sector_id' => $subSectorId,
                        'user_permissions' => $userPermissions,
                    ],
                ], 403);
            }
    
            return response()->json([
                'message' => 'Permission details',
                'permissions' => $userPermissions,
            ], 200);
        }
    
        // Case 2: Sector ID or Sub-Sector ID provided
        $hasPermission = \DB::table('user_permission_sectors')
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
    
        if (!$hasPermission) {
            $userPermissions = \DB::table('user_permission_sectors')
                ->join('permissions', 'user_permission_sectors.permission_id', '=', 'permissions.id')
                ->where('user_permission_sectors.user_id', $user->id)
                ->select('user_permission_sectors.*', 'permissions.name as permission_name')
                ->get();
    
            return response()->json([
                'message' => 'Permission denied or expired for this sector or sub-sector.',
                'debug' => [
                    'checked_permission' => $permission,
                    'sector_id' => $sectorId,
                    'sub_sector_id' => $subSectorId,
                    'user_permissions' => $userPermissions,
                ],
            ], 403);
        }
    
        return $next($request);
    }
}