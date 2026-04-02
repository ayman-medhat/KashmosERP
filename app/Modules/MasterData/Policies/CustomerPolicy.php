<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Customer;

class CustomerPolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.customers.view-any');
    }

    public function view(User $user, Customer $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.customers.create');
    }

    public function update(User $user, Customer $record): bool
    {
        return $this->can($user, 'master-data.customers.update');
    }

    public function delete(User $user, Customer $record): bool
    {
        return $this->can($user, 'master-data.customers.delete');
    }
}
