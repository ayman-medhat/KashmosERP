<?php

namespace App\Modules\Inventory\Policies;

use App\Core\Models\User;
use App\Modules\Inventory\Models\StockMovement;

class StockMovementPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('inventory.stock-movements.view-any');
    }

    public function view(User $user, StockMovement $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.stock-movements.create');
    }

    public function update(User $user, StockMovement $record): bool
    {
        return false;
    }

    public function delete(User $user, StockMovement $record): bool
    {
        return false;
    }
}
