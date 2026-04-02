<?php

namespace App\Core\Policies;

use App\Core\Models\AuditLog;
use App\Core\Models\User;

class AuditLogPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('core.audit-logs.view-any');
    }

    public function view(User $user, AuditLog $record): bool
    {
        return $user->can('core.audit-logs.view-any');
    }
}
