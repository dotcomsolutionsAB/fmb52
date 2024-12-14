<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionRoleController extends Controller
{
    /**
     * Create a single permission with validity
     */
    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
            'valid_from' => $request->valid_from,
            'valid_to' => $request->valid_to,
        ]);

        return response()->json(['message' => 'Permission created successfully', 'permission' => $permission], 201);
    }

    /**
     * Create bulk permissions with validity
     */
    public function createBulkPermissions(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $createdPermissions = [];
        $existingPermissions = [];

        foreach ($request->permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName],
                [
                    'guard_name' => 'sanctum',
                    'valid_from' => $request->valid_from,
                    'valid_to' => $request->valid_to,
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $createdPermissions[] = $permission;
            } else {
                $existingPermissions[] = $permissionName;
            }
        }

        return response()->json([
            'message' => 'Bulk permissions processed successfully',
            'created_permissions' => $createdPermissions,
            'existing_permissions' => $existingPermissions
        ]);
    }

    /**
     * Create a role with validity
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
            'valid_from' => $request->valid_from,
            'valid_to' => $request->valid_to,
        ]);

        return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
    }

    /**
     * Add permissions to a role with validity
     */
    public function addPermissionsToRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'permissions' => 'required|array',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        foreach ($request->permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);

            // Attach validity to the pivot table
            $role->permissions()->updateExistingPivot(
                $permission->id,
                [
                    'valid_from' => $request->valid_from,
                    'valid_to' => $request->valid_to,
                ]
            );
        }

        return response()->json(['message' => 'Permissions added to role successfully', 'role' => $role], 200);
    }

    /**
     * Assign permissions to a user (model) with validity
     */
    public function assignPermissionsToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|exists:permissions,name',
            'permissions.*.valid_from' => 'nullable|date',
            'permissions.*.valid_to' => 'nullable|date|after_or_equal:permissions.*.valid_from',
            'sub_sector_ids' => 'required|array', // Always required and an array
            'sub_sector_ids.*' => 'required|integer|exists:t_sub_sector,id', // Validate sub-sector IDs
        ]);
    
        try {
            $user = User::findOrFail($request->user_id);
    
            // Fetch sector_ids based on the provided sub_sector_ids
            $sectorIds = \DB::table('t_sub_sector')
                ->whereIn('id', $request->sub_sector_ids)
                ->distinct()
                ->pluck('sector_id')
                ->toArray();
    
            // Store sector and sub-sector access in the users table
            $user->update([
                'sector_access_id' => json_encode($sectorIds),
                'sub_sector_access_id' => json_encode($request->sub_sector_ids),
            ]);
    
            foreach ($request->permissions as $permissionData) {
                $permission = Permission::where('name', $permissionData['name'])->first();
    
                if ($permission) {
                    // Assign permission to user
                    $user->givePermissionTo($permission);
    
                    // Update model_has_permissions with validity dates
                    \DB::table('model_has_permissions')->updateOrInsert(
                        [
                            'model_id' => $user->id,
                            'model_type' => get_class($user),
                            'permission_id' => $permission->id,
                        ],
                        [
                            'valid_from' => $permissionData['valid_from'] ?? null,
                            'valid_to' => $permissionData['valid_to'] ?? null,
                        ]
                    );
                }
            }
    
            return response()->json([
                'message' => 'Permissions and sector/sub-sector access assigned successfully.',
                'user_id' => $user->id,
                'permissions' => $request->permissions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while assigning permissions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Resolve IDs from the table based on the provided array or 'all'.
     *
     * @param string $table
     * @param array $ids
     * @return array
     */
    
    /**
     * Resolve sector IDs based on input
     */
   

    /**
     * Get valid permissions for a user
     */
    public function getUserPermissions($userId)
{
    $user = User::findOrFail($userId);

    // Fetch permissions with validity conditions
    $permissions = $user->permissions()
        ->where(function ($query) {
            $query->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
        })
        ->where(function ($query) {
            $query->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now());
        })
        ->get();

    // Include sector and sub-sector access IDs
    $sectorAccessIds = json_decode($user->sector_access_id, true) ?? [];
    $subSectorAccessIds = json_decode($user->sub_sector_access_id, true) ?? [];

    return response()->json([
        'user' => $user,
        'permissions' => $permissions,
        'sector_access_ids' => $sectorAccessIds,
        'sub_sector_access_ids' => $subSectorAccessIds,
    ], 200);
}

    /**
     * Get valid permissions for a role
     */
    public function getRolePermissions($roleName)
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $permissions = $role->permissions()
            ->where(function ($query) {
                $query->whereNull('valid_from')
                      ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_to')
                      ->orWhere('valid_to', '>=', now());
            })
            ->get();

        return response()->json(['role' => $role, 'permissions' => $permissions], 200);
    }

    public function removePermissionsFromUser(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'permissions' => 'nullable|array', // Permissions array is optional
        'permissions.*.name' => 'required|string|exists:permissions,name',
        'sub_sector_ids' => 'nullable|array', // Sub-sector IDs array is optional
        'sub_sector_ids.*' => 'required|integer|exists:t_sub_sector,id',
    ]);

    try {
        $user = User::findOrFail($request->user_id);

        // Handle permissions removal if provided
        if (!empty($request->permissions)) {
            foreach ($request->permissions as $permissionData) {
                $permission = Permission::where('name', $permissionData['name'])->first();
                
                if ($permission) {
                    // Revoke permission from user
                    $user->revokePermissionTo($permission);

                    // Remove validity details from model_has_permissions
                    \DB::table('model_has_permissions')->where([
                        'model_id' => $user->id,
                        'model_type' => get_class($user),
                        'permission_id' => $permission->id,
                    ])->delete();
                }
            }
        }

        // Handle sub-sector access removal if provided
        if (!empty($request->sub_sector_ids)) {
            // Decode existing sub-sector and sector access
            $existingSubSectors = json_decode($user->sub_sector_access_id, true) ?? [];
            $existingSectors = json_decode($user->sector_access_id, true) ?? [];

            // Filter out the sub-sector IDs to be removed
            $updatedSubSectors = array_diff($existingSubSectors, $request->sub_sector_ids);

            // Fetch the remaining sector IDs based on updated sub-sectors
            $updatedSectors = \DB::table('t_sub_sector')
                ->whereIn('id', $updatedSubSectors)
                ->distinct()
                ->pluck('sector_id')
                ->toArray();

            // Update the user's sector and sub-sector access
            $user->update([
                'sector_access_id' => json_encode($updatedSectors),
                'sub_sector_access_id' => json_encode($updatedSubSectors),
            ]);
        }

        return response()->json([
            'message' => 'Permissions and sector/sub-sector access removed successfully.',
            'user_id' => $user->id,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while removing permissions.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}