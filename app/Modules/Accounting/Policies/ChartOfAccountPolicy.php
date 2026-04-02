<?php

namespace App\Modules\Accounting\Policies;

use App\Core\Models\User;
use App\Modules\Accounting\Models\ChartOfAccount;

class ChartOfAccountPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('accounting.chart-of-accounts.view-any');
    }

    public function view(User $user, ChartOfAccount $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('accounting.chart-of-accounts.create');
    }

    public function update(User $user, ChartOfAccount $record): bool
    {
        return $user->can('accounting.chart-of-accounts.update');
    }

    public function delete(User $user, ChartOfAccount $record): bool
    {
        return false;
    }
}

