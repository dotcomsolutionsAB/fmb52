<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionRoleController extends Controller
{
    /**
     * Create a single permission
     */
    public function createPermission(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:permissions,name']);

        if (Permission::where('name', $request->name)->exists()) {
            return response()->json(['message' => 'Permission already exists'], 409); // Conflict response
        }

        $permission = Permission::create(['name' => $request->name]);
        return response()->json(['message' => 'Permission created successfully', 'permission' => $permission], 201);
    }

    /**
     * Create bulk permissions
     */
    public function createBulkPermissions(Request $request)
    {
        $request->validate(['permissions' => 'required|array']);

        $createdPermissions = [];
        $existingPermissions = [];

        foreach ($request->permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
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
     * Get all permissions
     */
    public function getAllPermissions()
    {
        $permissions = Permission::all();
        return response()->json(['permissions' => $permissions], 200);
    }

    /**
     * Delete a single permission
     */
    public function deletePermission(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        $permission = Permission::where('name', $request->name)->first();

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404); // Not found response
        }

        $permission->delete();
        return response()->json(['message' => 'Permission deleted successfully'], 200);
    }

    /**
     * Delete bulk permissions
     */
    public function deleteBulkPermissions(Request $request)
    {
        $request->validate(['permissions' => 'required|array']);
        $deletedPermissions = Permission::whereIn('name', $request->permissions)->delete();

        return response()->json(['message' => "$deletedPermissions permissions deleted successfully"], 200);
    }

    /**
     * Create a role
     */
    public function createRole(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);

        if (Role::where('name', $request->name)->exists()) {
            return response()->json(['message' => 'Role already exists'], 409); // Conflict response
        }

        $role = Role::create(['name' => $request->name]);
        return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
    }

    /**
     * Get all roles
     */
    public function getAllRoles()
    {
        $roles = Role::all();
        return response()->json(['roles' => $roles], 200);
    }

    /**
     * Add single or bulk permissions to a role
     */
    public function addPermissionsToRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'permissions' => 'required|array'
        ]);

        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->syncPermissions($request->permissions);
        return response()->json(['message' => 'Permissions added to role successfully', 'role' => $role], 200);
    }

    /**
     * Edit role name
     */
    public function editRole(Request $request)
    {
        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|unique:roles,name'
        ]);

        $role = Role::where('name', $request->old_name)->first();
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->name = $request->new_name;
        $role->save();

        return response()->json(['message' => 'Role name updated successfully', 'role' => $role], 200);
    }

    /**
     * Delete a role
     */
    public function deleteRole(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        try {
            // Find the role by name
            $role = Role::where('name', $request->name)->first();

            // If the role does not exist, return a 404 error
            if (!$role) {
                return response()->json(['message' => 'Role not found'], 404);
            }

            // Detach all permissions associated with the role
            if ($role->permissions()->exists()) {
                $role->permissions()->detach();
            }

            // Detach the role from all users
            if ($role->users()->exists()) {
                $role->users()->detach();
            }

            // Delete the role
            $role->delete();

            return response()->json(['message' => 'Role deleted successfully'], 200);
        } catch (\Exception $e) {
            // Catch any unexpected errors and log them
            return response()->json(['message' => 'Failed to delete role', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Assign permissions to a user (model)
     */
    public function assignPermissionsToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|array'
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            // Validate that all permissions exist with the correct guard
            $permissionsExist = Permission::where('guard_name', 'sanctum')
                ->whereIn('name', $request->permissions)
                ->count();

            if ($permissionsExist !== count($request->permissions)) {
                return response()->json([
                    'message' => 'One or more permissions do not exist for the sanctum guard',
                    'missing_permissions' => array_diff($request->permissions, Permission::where('guard_name', 'sanctum')->pluck('name')->toArray())
                ], 422); // Unprocessable Entity
            }

            // Assign permissions explicitly for the sanctum guard
            $permissions = Permission::where('guard_name', 'sanctum')
                ->whereIn('name', $request->permissions)
                ->get();

            $user->syncPermissions($permissions);

            return response()->json([
                'message' => 'Permissions assigned to user successfully',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while assigning permissions',
                'error' => $e->getMessage()
            ], 500); // Internal Server Error
        }
    }



    /**
     * Get all permissions for a user
     */
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
        $permissions = $user->getAllPermissions();

        return response()->json(['user' => $user, 'permissions' => $permissions], 200);
    }

    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($roleName)
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $permissions = $role->permissions;

        return response()->json(['role' => $role, 'permissions' => $permissions], 200);
    }
}
