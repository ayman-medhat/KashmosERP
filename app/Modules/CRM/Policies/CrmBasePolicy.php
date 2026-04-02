<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmBasePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('crm.view');
    }

    public function view(User $user, object $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('crm.create');
    }

    public function update(User $user, object $record): bool
    {
        return $user->can('crm.edit');
    }

    public function delete(User $user, object $record): bool
    {
        return $user->can('crm.delete');
    }
}

