<?php

namespace App\Core\Policies;

use App\Core\Models\Permission;
use App\Core\Models\User;

class PermissionPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('core.permissions.view-any');
    }

    public function view(User $user, Permission $record): bool
    {
        return $user->can('core.permissions.view-any');
    }

    public function update(User $user, Permission $record): bool
    {
        return $user->can('core.permissions.update');
    }
}
