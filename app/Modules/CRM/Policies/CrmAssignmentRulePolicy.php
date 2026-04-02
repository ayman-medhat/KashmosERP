<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmAssignmentRulePolicy extends CrmBasePolicy
{
    public function create(User $user): bool
    {
        return $user->can('crm.manage_rules');
    }

    public function update(User $user, object $record): bool
    {
        return $user->can('crm.manage_rules');
    }

    public function delete(User $user, object $record): bool
    {
        return $user->can('crm.manage_rules');
    }
}

