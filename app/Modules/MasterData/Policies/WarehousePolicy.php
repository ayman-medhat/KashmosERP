<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Warehouse;

class WarehousePolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.warehouses.view-any');
    }

    public function view(User $user, Warehouse $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.warehouses.create');
    }

    public function update(User $user, Warehouse $record): bool
    {
        return $this->can($user, 'master-data.warehouses.update');
    }

    public function delete(User $user, Warehouse $record): bool
    {
        return $this->can($user, 'master-data.warehouses.delete');
    }
}
