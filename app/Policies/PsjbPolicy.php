<?php

namespace App\Policies;

use App\Models\Psjb;
use App\Models\User;
use App\UserRole;

class PsjbPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, Psjb $psjb): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($psjb->salesCase->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }
}
