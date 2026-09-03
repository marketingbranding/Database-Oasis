<?php

namespace App\Policies;

use App\Models\AkadTarget;
use App\Models\User;
use App\UserRole;

class AkadTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Management, UserRole::Auditor, UserRole::SuperAdmin]);
    }

    public function view(User $user, AkadTarget $target): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Management, UserRole::Auditor, UserRole::SuperAdmin])
            || $user->belongsToBranch($target->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function update(User $user, AkadTarget $target): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }

    public function delete(User $user, AkadTarget $target): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
