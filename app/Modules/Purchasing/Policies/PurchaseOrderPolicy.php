<?php

namespace App\Modules\Purchasing\Policies;

use App\Core\Models\User;
use App\Modules\Purchasing\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.purchase-orders.view-any');
    }

    public function view(User $user, PurchaseOrder $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.purchase-orders.create');
    }

    public function update(User $user, PurchaseOrder $record): bool
    {
        return $user->can('purchasing.purchase-orders.update');
    }

    public function approve(User $user, PurchaseOrder $record): bool
    {
        return $user->can('purchasing.purchase-orders.approve');
    }

    public function submit(User $user, PurchaseOrder $record): bool
    {
        return $user->can('purchasing.purchase-orders.submit');
    }

    public function cancel(User $user, PurchaseOrder $record): bool
    {
        return $user->can('purchasing.purchase-orders.cancel');
    }
}
