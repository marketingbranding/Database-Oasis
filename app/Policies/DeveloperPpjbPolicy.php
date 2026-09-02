<?php

namespace App\Policies;

use App\Models\DeveloperPpjb;
use App\Models\User;
use App\UserRole;

class DeveloperPpjbPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, DeveloperPpjb $record): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor]) || $user->belongsToBranch($record->salesCase->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }
}
