<?php

namespace App\Policies;

use App\Models\SalesCase;
use App\Models\User;
use App\UserRole;

class SalesCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, SalesCase $case): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($case->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }

    public function update(User $user, SalesCase $case): bool
    {
        return $user->hasRole(UserRole::HqAdmin)
            || ($user->hasRole(UserRole::BranchAdmin) && $user->belongsToBranch($case->branch_id));
    }
}
