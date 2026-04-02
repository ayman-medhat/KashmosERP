<?php

namespace App\Modules\Accounting\Policies;

use App\Core\Models\User;
use App\Modules\Accounting\Models\JournalEntry;

class JournalEntryPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('accounting.journal-entries.view-any');
    }

    public function view(User $user, JournalEntry $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('accounting.journal-entries.create');
    }

    public function post(User $user, JournalEntry $record): bool
    {
        return $user->can('accounting.journal-entries.post');
    }

    public function update(User $user, JournalEntry $record): bool
    {
        return false;
    }

    public function delete(User $user, JournalEntry $record): bool
    {
        return false;
    }
}

