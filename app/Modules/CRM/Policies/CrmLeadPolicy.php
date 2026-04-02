<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmLeadPolicy extends CrmBasePolicy
{
    public function assign(User $user, object $record): bool
    {
        return $user->can('crm.assign');
    }

    public function convert(User $user, object $record): bool
    {
        return $user->can('crm.convert_lead');
    }
}

