<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmOpportunityPolicy extends CrmBasePolicy
{
    public function assign(User $user, object $record): bool
    {
        return $user->can('crm.assign');
    }

    public function moveStage(User $user, object $record): bool
    {
        return $user->can('crm.move_stage');
    }
}

