<?php

namespace App\Modules\Purchasing\Policies;

use App\Core\Models\User;
use App\Modules\Purchasing\Models\SupplierBill;

class SupplierBillPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.supplier-bills.view-any');
    }

    public function view(User $user, SupplierBill $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.supplier-bills.create');
    }

    public function post(User $user, SupplierBill $record): bool
    {
        return $user->can('purchasing.supplier-bills.post');
    }

    public function update(User $user, SupplierBill $record): bool
    {
        return false;
    }

    public function delete(User $user, SupplierBill $record): bool
    {
        return false;
    }
}

