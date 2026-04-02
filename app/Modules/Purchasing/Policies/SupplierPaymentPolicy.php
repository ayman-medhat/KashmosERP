<?php

namespace App\Modules\Purchasing\Policies;

use App\Core\Models\User;
use App\Modules\Purchasing\Models\SupplierPayment;

class SupplierPaymentPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.supplier-payments.view-any');
    }

    public function view(User $user, SupplierPayment $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.supplier-payments.create');
    }

    public function update(User $user, SupplierPayment $record): bool
    {
        return false;
    }

    public function delete(User $user, SupplierPayment $record): bool
    {
        return false;
    }
}

