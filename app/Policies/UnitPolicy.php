<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\UserRole;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($unit->project->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->hasRole(UserRole::HqAdmin)
            || ($user->hasRole(UserRole::BranchAdmin) && $user->belongsToBranch($unit->project->branch_id));
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
