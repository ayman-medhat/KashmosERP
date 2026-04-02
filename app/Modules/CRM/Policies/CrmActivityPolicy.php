<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmActivityPolicy extends CrmBasePolicy
{
    public function complete(User $user, object $record): bool
    {
        return $user->can('crm.complete_activity');
    }
}

