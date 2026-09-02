<?php

namespace App\Policies;

use App\Models\Consumer;
use App\Models\User;
use App\UserRole;

class ConsumerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, Consumer $consumer): bool
    {
        if ($user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])) {
            return true;
        }

        return $user->branch_id !== null
            && $consumer->salesCases()
                ->where('branch_id', $user->branch_id)
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function update(User $user, Consumer $consumer): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function delete(User $user, Consumer $consumer): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
