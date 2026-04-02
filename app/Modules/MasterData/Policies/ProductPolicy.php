<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Product;

class ProductPolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.products.view-any');
    }

    public function view(User $user, Product $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.products.create');
    }

    public function update(User $user, Product $record): bool
    {
        return $this->can($user, 'master-data.products.update');
    }

    public function delete(User $user, Product $record): bool
    {
        return $this->can($user, 'master-data.products.delete');
    }
}
