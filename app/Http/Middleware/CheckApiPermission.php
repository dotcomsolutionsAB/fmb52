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

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // If `sector_id` and `sub_sector_id` are not required, skip the check
        $sectorId = $request->input('sector_id');
        $subSectorId = $request->input('sub_sector_id');

        if (($permission || $role) && (!$sectorId || !$subSectorId)) {
            return response()->json(['message' => 'Sector ID and Sub-Sector ID are required for permission or role validation.'], 403);
        }

        // Check permissions with sector_ids, sub_sector_ids, and validity
        if ($permission) {
            $hasPermission = $user->permissions()
                ->where('name', $permission)
                ->when($sectorId, function ($query) use ($sectorId) {
                    $query->whereRaw("FIND_IN_SET(?, sector_ids)", [$sectorId]);
                })
                ->when($subSectorId, function ($query) use ($subSectorId) {
                    $query->whereRaw("FIND_IN_SET(?, sub_sector_ids)", [$subSectorId]);
                })
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
                return response()->json(['message' => 'Permission denied or expired for this sector or sub-sector.'], 403);
            }
        }

        // Check roles with sector_ids, sub_sector_ids, and validity
        if ($role) {
            $hasRole = $user->roles()
                ->where('name', $role)
                ->when($sectorId, function ($query) use ($sectorId) {
                    $query->whereRaw("FIND_IN_SET(?, sector_ids)", [$sectorId]);
                })
                ->when($subSectorId, function ($query) use ($subSectorId) {
                    $query->whereRaw("FIND_IN_SET(?, sub_sector_ids)", [$subSectorId]);
                })
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
                return response()->json(['message' => 'Role access denied or expired for this sector or sub-sector.'], 403);
            }
        }

        return $next($request);
    }
}