<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;

class RoleService
{
    public function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    public function hasRole(User $user, string $roleName): bool
    {
        return $user->roles()->where('name', $roleName)->exists();
    }
}
