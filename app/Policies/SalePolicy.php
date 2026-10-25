<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, UserRole::cases(), true);
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->role === UserRole::Owner
            || $user->role === UserRole::Manager
            || $sale->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }
}
