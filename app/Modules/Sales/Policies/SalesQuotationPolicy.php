<?php

namespace App\Modules\Sales\Policies;

use App\Core\Models\User;
use App\Modules\Sales\Models\SalesQuotation;

class SalesQuotationPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('sales.quotations.view-any');
    }

    public function view(User $user, SalesQuotation $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.quotations.create');
    }

    public function update(User $user, SalesQuotation $record): bool
    {
        return $user->can('sales.quotations.update');
    }

    public function submit(User $user, SalesQuotation $record): bool
    {
        return $user->can('sales.quotations.submit');
    }

    public function approve(User $user, SalesQuotation $record): bool
    {
        return $user->can('sales.quotations.approve');
    }

    public function convert(User $user, SalesQuotation $record): bool
    {
        return $user->can('sales.quotations.convert');
    }

    public function cancel(User $user, SalesQuotation $record): bool
    {
        return $user->can('sales.quotations.cancel');
    }
}
