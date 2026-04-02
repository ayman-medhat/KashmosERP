<?php

namespace App\Modules\Sales\Policies;

use App\Core\Models\User;
use App\Modules\Sales\Models\SalesInvoice;

class SalesInvoicePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('sales.sales-invoices.view-any');
    }

    public function view(User $user, SalesInvoice $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.sales-invoices.create');
    }

    public function post(User $user, SalesInvoice $record): bool
    {
        return $user->can('sales.sales-invoices.post');
    }

    public function update(User $user, SalesInvoice $record): bool
    {
        return false;
    }

    public function delete(User $user, SalesInvoice $record): bool
    {
        return false;
    }
}

