<?php

namespace App\Policies;

use App\Models\AkadRecord;
use App\Models\User;
use App\UserRole;

class AkadRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, AkadRecord $record): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor]) || $user->belongsToBranch($record->salesCase->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }
}
