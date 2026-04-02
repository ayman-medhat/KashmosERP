<?php

namespace App\Core\Policies;

use App\Core\Models\Role;
use App\Core\Models\User;

class RolePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('core.roles.view-any');
    }

    public function view(User $user, Role $record): bool
    {
        return $user->can('core.roles.view-any');
    }

    public function create(User $user): bool
    {
        return $user->can('core.roles.create');
    }

    public function update(User $user, Role $record): bool
    {
        return $user->can('core.roles.update');
    }

    public function delete(User $user, Role $record): bool
    {
        return $user->can('core.roles.delete') && ! $record->is_system;
    }
}
