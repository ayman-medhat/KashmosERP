<?php

namespace App\Modules\CRM\Policies;

use App\Core\Models\User;

class CrmPipelineStagePolicy extends CrmBasePolicy
{
    public function create(User $user): bool
    {
        return $user->can('crm.manage_pipeline');
    }

    public function update(User $user, object $record): bool
    {
        return $user->can('crm.manage_pipeline');
    }

    public function delete(User $user, object $record): bool
    {
        return $user->can('crm.manage_pipeline');
    }
}

