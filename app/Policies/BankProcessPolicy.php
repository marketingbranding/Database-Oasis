<?php

namespace App\Policies;

use App\Models\BankProcess;
use App\Models\User;
use App\UserRole;

class BankProcessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, BankProcess $process): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($process->salesCase->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }
}
