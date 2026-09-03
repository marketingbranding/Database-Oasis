<?php

namespace App\Policies;

use App\Models\LegacyMigrationCandidate;
use App\Models\User;
use App\UserRole;

class LegacyMigrationCandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin, UserRole::Auditor, UserRole::Management]);
    }

    public function view(User $user, LegacyMigrationCandidate $candidate): bool
    {
        return $this->viewAny($user);
    }

    public function review(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin]);
    }

    public function resolve(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin]);
    }
}
