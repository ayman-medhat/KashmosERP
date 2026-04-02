<?php

namespace App\Modules\Purchasing\Policies;

use App\Core\Models\User;
use App\Modules\Purchasing\Models\PurchaseReceipt;

class PurchaseReceiptPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.purchase-receipts.view-any');
    }

    public function view(User $user, PurchaseReceipt $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.purchase-receipts.create')
            && $user->can('purchasing.purchase-receipts.confirm');
    }

    public function confirm(User $user, PurchaseReceipt $record): bool
    {
        return $user->can('purchasing.purchase-receipts.confirm');
    }

    public function update(User $user, PurchaseReceipt $record): bool
    {
        return false;
    }

    public function delete(User $user, PurchaseReceipt $record): bool
    {
        return false;
    }
}
