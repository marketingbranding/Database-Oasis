<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\UserRole;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($branch->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
