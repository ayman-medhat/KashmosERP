<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmLeadSourcePolicy extends CrmBasePolicy
{
    public function create(User $user): bool
    {
        return $user->can('crm.manage_sources');
    }

    public function update(User $user, object $record): bool
    {
        return $user->can('crm.manage_sources');
    }

    public function delete(User $user, object $record): bool
    {
        return $user->can('crm.manage_sources');
    }
}

