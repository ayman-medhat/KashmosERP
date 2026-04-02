<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Supplier;

class SupplierPolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.suppliers.view-any');
    }

    public function view(User $user, Supplier $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.suppliers.create');
    }

    public function update(User $user, Supplier $record): bool
    {
        return $this->can($user, 'master-data.suppliers.update');
    }

    public function delete(User $user, Supplier $record): bool
    {
        return $this->can($user, 'master-data.suppliers.delete');
    }
}
