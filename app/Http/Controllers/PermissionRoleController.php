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
        $permission = Permission::create(['name' => $request->name]);
        return response()->json(['message' => 'Permission created successfully', 'permission' => $permission]);
    }

    /**
     * Create bulk permissions
     */
    public function createBulkPermissions(Request $request)
    {
        $request->validate(['permissions' => 'required|array']);
        $createdPermissions = [];
        foreach ($request->permissions as $permissionName) {
            $createdPermissions[] = Permission::firstOrCreate(['name' => $permissionName]);
        }
        return response()->json(['message' => 'Bulk permissions created successfully', 'permissions' => $createdPermissions]);
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
        Permission::where('name', $request->name)->delete();
        return response()->json(['message' => 'Permission deleted successfully']);
    }

    /**
     * Delete bulk permissions
     */
    public function deleteBulkPermissions(Request $request)
    {
        $request->validate(['permissions' => 'required|array']);
        Permission::whereIn('name', $request->permissions)->delete();
        return response()->json(['message' => 'Bulk permissions deleted successfully']);
    }

    /**
     * Create a role
     */
    public function createRole(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);
        $role = Role::create(['name' => $request->name]);
        return response()->json(['message' => 'Role created successfully', 'role' => $role]);
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
        $role = Role::findByName($request->role);
        $role->syncPermissions($request->permissions);
        return response()->json(['message' => 'Permissions added to role successfully', 'role' => $role]);
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
        $role = Role::findByName($request->old_name);
        $role->name = $request->new_name;
        $role->save();
        return response()->json(['message' => 'Role name updated successfully', 'role' => $role]);
    }

    /**
     * Delete a role
     */
    public function deleteRole(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        Role::where('name', $request->name)->delete();
        return response()->json(['message' => 'Role deleted successfully']);
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
        $user = User::findOrFail($request->user_id);
        $user->syncPermissions($request->permissions);
        return response()->json(['message' => 'Permissions assigned to user successfully', 'user' => $user]);
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
        $permissions = $user->getAllPermissions();
        return response()->json(['user' => $user, 'permissions' => $permissions]);
    }

    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($roleName)
    {
        $role = Role::findByName($roleName);
        $permissions = $role->permissions;
        return response()->json(['role' => $role, 'permissions' => $permissions]);
    }
}
