<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, UserRole::Owner, UserRole::Manager, UserRole::Cashier);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->hasAnyRole($user, UserRole::Owner, UserRole::Manager);
    }

    private function hasAnyRole(User $user, UserRole ...$roles): bool
    {
        return in_array($user->role, $roles, true);
    }
}
