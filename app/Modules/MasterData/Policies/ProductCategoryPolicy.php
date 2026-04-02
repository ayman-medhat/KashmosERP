<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;
use App\Modules\MasterData\Models\ProductCategory;

class ProductCategoryPolicy extends MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'master-data.product-categories.view-any');
    }

    public function view(User $user, ProductCategory $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'master-data.product-categories.create');
    }

    public function update(User $user, ProductCategory $record): bool
    {
        return $this->can($user, 'master-data.product-categories.update');
    }

    public function delete(User $user, ProductCategory $record): bool
    {
        return $this->can($user, 'master-data.product-categories.delete');
    }
}
