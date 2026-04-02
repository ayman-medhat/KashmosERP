<?php

namespace App\Modules\Sales\Policies;

use App\Core\Models\User;
use App\Modules\Sales\Models\SalesReceipt;

class SalesReceiptPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('sales.sales-receipts.view-any');
    }

    public function view(User $user, SalesReceipt $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.sales-receipts.create');
    }

    public function update(User $user, SalesReceipt $record): bool
    {
        return false;
    }

    public function delete(User $user, SalesReceipt $record): bool
    {
        return false;
    }
}

