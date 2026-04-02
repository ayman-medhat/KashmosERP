<?php

namespace App\Modules\Sales\Policies;

use App\Core\Models\User;
use App\Modules\Sales\Models\SalesOrder;

class SalesOrderPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('sales.sales-orders.view-any');
    }

    public function view(User $user, SalesOrder $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.sales-orders.create');
    }

    public function update(User $user, SalesOrder $record): bool
    {
        return $user->can('sales.sales-orders.update');
    }

    public function approve(User $user, SalesOrder $record): bool
    {
        return $user->can('sales.sales-orders.approve');
    }

    public function submit(User $user, SalesOrder $record): bool
    {
        return $user->can('sales.sales-orders.submit');
    }

    public function cancel(User $user, SalesOrder $record): bool
    {
        return $user->can('sales.sales-orders.cancel');
    }
}
