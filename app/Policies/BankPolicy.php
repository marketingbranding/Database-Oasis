<?php

namespace App\Policies;

use App\Models\Bank;
use App\Models\User;
use App\UserRole;

class BankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, Bank $bank): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function update(User $user, Bank $bank): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function delete(User $user, Bank $bank): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
