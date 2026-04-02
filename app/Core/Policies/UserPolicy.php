<?php

namespace App\Core\Policies;

use App\Core\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('core.users.view-any');
    }

    public function view(User $user, User $record): bool
    {
        return $user->can('core.users.view-any');
    }

    public function create(User $user): bool
    {
        return $user->can('core.users.create');
    }

    public function update(User $user, User $record): bool
    {
        return $user->can('core.users.update');
    }

    public function delete(User $user, User $record): bool
    {
        return $user->can('core.users.delete') && ! $record->hasRole('super-admin');
    }
}
