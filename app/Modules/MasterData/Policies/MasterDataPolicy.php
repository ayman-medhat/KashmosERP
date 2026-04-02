<?php

namespace App\Modules\MasterData\Policies;

use App\Core\Models\User;

class MasterDataPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    protected function can(User $user, string $ability): bool
    {
        return $user->can($ability);
    }
}
