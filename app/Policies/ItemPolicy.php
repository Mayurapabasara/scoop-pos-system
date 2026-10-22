<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, UserRole::Owner, UserRole::Manager, UserRole::Cashier);
    }

    public function view(User $user, Item $item): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, UserRole::Owner, UserRole::Manager);
    }

    public function update(User $user, Item $item): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->role === UserRole::Owner;
    }

    private function hasAnyRole(User $user, UserRole ...$roles): bool
    {
        return in_array($user->role, $roles, true);
    }
}
