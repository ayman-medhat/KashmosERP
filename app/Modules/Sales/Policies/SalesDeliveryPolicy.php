<?php

namespace App\Modules\Sales\Policies;

use App\Core\Models\User;
use App\Modules\Sales\Models\SalesDelivery;

class SalesDeliveryPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('sales.sales-deliveries.view-any');
    }

    public function view(User $user, SalesDelivery $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.sales-deliveries.create')
            && $user->can('sales.sales-deliveries.confirm');
    }

    public function confirm(User $user, SalesDelivery $record): bool
    {
        return $user->can('sales.sales-deliveries.confirm');
    }

    public function update(User $user, SalesDelivery $record): bool
    {
        return false;
    }

    public function delete(User $user, SalesDelivery $record): bool
    {
        return false;
    }
}
