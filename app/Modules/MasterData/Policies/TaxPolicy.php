<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Tax;

class TaxPolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.taxes.view-any');
    }

    public function view(User $user, Tax $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.taxes.create');
    }

    public function update(User $user, Tax $record): bool
    {
        return $this->can($user, 'master-data.taxes.update');
    }

    public function delete(User $user, Tax $record): bool
    {
        return $this->can($user, 'master-data.taxes.delete');
    }
}
