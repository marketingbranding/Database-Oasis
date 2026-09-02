<?php

namespace App\Policies;

use App\Models\BiCheck;
use App\Models\User;
use App\UserRole;

class BiCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, BiCheck $biCheck): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($biCheck->salesCase->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }
}
