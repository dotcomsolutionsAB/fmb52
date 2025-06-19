<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
           
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
           
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
        'role_id' => 'required|integer|in:1,2,3,4',
        'permissions' => 'required|array',
        'permissions.*.name' => 'required|string|exists:permissions,name',
        'permissions.*.valid_from' => 'nullable|date',
        'permissions.*.valid_to' => 'nullable|date|after_or_equal:permissions.*.valid_from',
        'sector_id' => 'required|array|exists:t_sector,id',
        'sector_id.*' => 'nullable|integer|exists:t_sector,id',
        'sub_sector_ids' => 'nullable|array',
        'sub_sector_ids.*' => 'nullable|integer|exists:t_sub_sector,id',
    ]);

    try {
        $user = User::findOrFail($request->user_id);
        $roleId = $request->role_id;
        $sectorIds = $request->sector_id;

        
        $subSectorIds = null; // default

        if ($roleId == 1) {
             if (count($request->sector_id) > 1) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Sector Admin can be assigned only one Sector'
                ], 422);
            }
            // ✅ Sector Admin: only sector access, no sub-sectors

            $subSectorIds = null;
        } elseif ($roleId == 2) {
            // ✅ Masool: max 4 sub-sectors
            if (count($request->sub_sector_ids) > 4) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Masool can be assigned a maximum of 4 sub-sectors.'
                ], 422);
            }
            $subSectorIds = $request->sub_sector_ids;
        } elseif ($roleId == 3) {
            // ✅ Musaid: exactly 1 sub-sector
            if (count($request->sub_sector_ids) !== 1) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Musaid must be assigned exactly 1 sub-sector.'
                ], 422);
            }
            $subSectorIds = $request->sub_sector_ids;
        } elseif ($roleId == 4) {
            // ✅ Coordinator: accept as-is
            $subSectorIds = $request->sub_sector_ids;
        }

        // If sub-sectors are provided, extract sector_ids from them (for roles 2-4)
        if (!is_null($subSectorIds)) {
            $sectorIds = \DB::table('t_sub_sector')
                ->whereIn('id', $subSectorIds)
                ->pluck('sector_id')
                ->unique()
                ->toArray();
        }

        // ✅ Save role and access details in user
        $user->update([
            'sector_access_id' => json_encode($sectorIds),
            'sub_sector_access_id' => is_null($subSectorIds) ? null : json_encode($subSectorIds),
            'access_role_id' => $roleId,
        ]);

        // ✅ Assign permissions with validity dates
        foreach ($request->permissions as $permissionData) {
            $permission = Permission::where('name', $permissionData['name'])->first();

            if ($permission) {
                $user->givePermissionTo($permission);

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
            'message' => 'Permissions and access saved successfully.',
            'user_id' => $user->id,
            'access_role_id' => $roleId,
            'sector_id' => $sectorIds,
            'sub_sector_ids' => $subSectorIds,
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
        // Fetch the role by name
        $role = Role::where('id', $roleName)->first();
    
        // If the role is not found, return a 404 error
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
    
        // Retrieve all permissions associated with the role
        $permissions = $role->permissions()->get();
    
        // Return the role and permissions in the response
        return response()->json([
            'role' => $role,
            'permissions' => $permissions
        ], 200);
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

    public function getAllPermissions(Request $request)
    {
        // Fetch all permissions, optionally paginate
        $permissions = Permission::all(); // You can replace `all` with `paginate($perPage)` if needed.

        // Return response
        return response()->json([
            'success' => true,
            'permissions' => $permissions
        ], 200);
    }
    public function getAllRoles(Request $request)
    {
        // Fetch all permissions, optionally paginate
        $roles = Role::all(); // You can replace `all` with `paginate($perPage)` if needed.

        // Return response
        return response()->json([
            'success' => true,
            'roles' => $roles
        ], 200);
    }
    public function createRoleWithPermissions(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            
            'remarks' => 'nullable|string|max:255',
        ]);

        // Retrieve jamiat_id from the authenticated user
        $jamiatId = auth()->user()->jamiat_id;

        // Create the role
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
            'jamiat_id' => $jamiatId,
            'remarks' => $request->remarks,
        ]);

        // Add permissions if provided
        if (!empty($request->permissions)) {
            foreach ($request->permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $role->givePermissionTo($permission);

                // Handle validity if provided
            
            }
        }

        return response()->json([
            'message' => 'Role created successfully with permissions',
            'role' => $role,
        ], 201);
    }
    
   public function getUsersWithPermissions($id = null)
{
    // Fetch base user and permissions data
    $users = DB::table('users')
        ->leftJoin('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
        ->leftJoin('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
        ->leftJoin('roles', 'users.access_role_id', '=', 'roles.id')
        ->select(
            'users.id as user_id',
            'users.its as its',
            'users.name as user_name',
            'users.mobile as mobile',
            'users.email as user_email',
            'users.role as user_role',
            'users.jamiat_id',
            'users.sector_access_id',
            'users.sub_sector_access_id',
            'roles.name as role_name',
            'permissions.name as permission_name',
            'model_has_permissions.valid_to as validity'
        )
        ->when($id, fn($q) => $q->where('users.id', $id))
        ->when(!$id, fn($q) => $q->where('users.role', 'mumeneen'))
        ->orderBy('users.name', 'asc')
        ->get();

    // Group by user_id and map
    $groupedUsers = $users->groupBy('user_id')->map(function ($userGroup) {
        $user = $userGroup->first();

        // Decode JSON strings
        $sectorIds = json_decode($user->sector_access_id ?? '[]', true);
        $subSectorIds = json_decode($user->sub_sector_access_id ?? '[]', true);

        // Fetch sectors and sub-sectors
        $sectorIds = is_array($sectorIds) ? $sectorIds : (is_null($sectorIds) ? [] : [$sectorIds]);
        $subSectorIds = is_array($subSectorIds) ? $subSectorIds : (is_null($subSectorIds) ? [] : [$subSectorIds]);

        $sectors = DB::table('t_sector')->whereIn('id', $sectorIds)->get(['id', 'name']);
        $subSectors = DB::table('t_sub_sector')->whereIn('id', $subSectorIds)->get(['id', 'name']);

        return [
            'user_id' => $user->user_id,
            'its' => $user->its,
            'user_name' => $user->user_name,
            'mobile' => $user->mobile,
            'user_email' => $user->user_email,
            'user_role' => $user->user_role,
            'jamiat_id' => $user->jamiat_id,
            'role_name' => $user->role_name,
            'sector_access' => $sectors,
            'sub_sector_access' => $subSectors,
            'permissions' => $userGroup->map(function ($permission) {
                return [
                    'permission_name' => $permission->permission_name,
                    'valid_to' => $permission->validity,
                ];
            })->unique('permission_name')->values(),
        ];
    })->values();

    return response()->json([
        'message' => 'Users with grouped permissions retrieved successfully.',
        'data' => $groupedUsers,
    ], 200);
}
    public function getPermissionsByRole($role_id)
{
    try {
        // Validate the role_id
       

        // Fetch role with permissions
        $role = Role::with('permissions:id,name')->findOrFail($role_id);

        // Format response
        $permissions = $role->permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        });

        return response()->json([
            'code'=>200,
            'success' => true,
            'message' => 'Permissions fetched for the role.',
            'data' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $permissions,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'code'=>401,
            'success' => false,
            'message' => 'Failed to fetch permissions for role.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function removePermissionsFromUserNew(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
    ]);

    try {
        $user = User::findOrFail($request->user_id);

        // Remove all permissions
        $user->permissions()->detach(); // from spatie's `HasPermissions` trait

        // Clean up model_has_permissions table for any validity meta
        \DB::table('model_has_permissions')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->delete();

        // Reset access role and access scopes
        $user->update([
            'access_role_id' => null,
            'sector_access_id' => null,
            'sub_sector_access_id' => null,
        ]);

        return response()->json([
            'message' => 'All permissions and access role removed successfully.',
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
