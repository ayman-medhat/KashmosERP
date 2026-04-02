<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Unit;

class UnitPolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.units.view-any');
    }

    public function view(User $user, Unit $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.units.create');
    }

    public function update(User $user, Unit $record): bool
    {
        return $this->can($user, 'master-data.units.update');
    }

    public function delete(User $user, Unit $record): bool
    {
        return $this->can($user, 'master-data.units.delete');
    }
}
