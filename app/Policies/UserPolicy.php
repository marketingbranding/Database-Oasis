<?php

namespace App\Policies;

use App\Models\User;
use App\UserRole;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::Auditor]);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || ($user->hasRole(UserRole::BranchAdmin) && $user->belongsToBranch($targetUser->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
