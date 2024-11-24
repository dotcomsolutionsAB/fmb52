<?php

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Relationship with permissions
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    // Relationship with users
    public function users()
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id');
    }
}


?>