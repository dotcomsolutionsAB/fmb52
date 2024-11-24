<?php

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Relationship with roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }
}


?>