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
            'sector_ids' => 'nullable|array',
            'sector_ids.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_int($value) && $value !== 'all') {
                        $fail("$attribute must be an integer or the string 'all'.");
                    }
                },
            ],
            'sub_sector_ids' => 'nullable|array',
            'sub_sector_ids.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_int($value) && $value !== 'all') {
                        $fail("$attribute must be an integer or the string 'all'.");
                    }
                },
            ],
        ]);
    
        try {
            $user = User::findOrFail($request->user_id);
    
            // Resolve sector and sub-sector IDs
            $sectorIds = $this->resolveIds('sectors', $request->sector_ids ?? []);
            $subSectorIds = $this->resolveIds('sub_sectors', $request->sub_sector_ids ?? []);
    
            // Store sector and sub-sector access in the users table
            $user->update([
                'sector_access_id' => $sectorIds ? json_encode($sectorIds) : null,
                'sub_sector_access_id' => $subSectorIds ? json_encode($subSectorIds) : null,
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
    private function resolveIds(string $table, array $ids): array
    {
        if (in_array('all', $ids)) {
            // If 'all' is provided, fetch all IDs from the table
            return \DB::table($table)->pluck('id')->toArray();
        }
    
        // Otherwise, return the given IDs
        return $ids;
    }
    
    /**
     * Resolve sector IDs based on input
     */
   

    /**
     * Get valid permissions for a user
     */
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
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

        return response()->json(['user' => $user, 'permissions' => $permissions], 200);
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
}