<?php

namespace App\Policies;

use App\Models\LegacyMigrationOrphan;
use App\Models\User;
use App\UserRole;

class LegacyMigrationOrphanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin, UserRole::Auditor, UserRole::Management]);
    }

    public function view(User $user, LegacyMigrationOrphan $orphan): bool
    {
        return $this->viewAny($user);
    }

    public function resolve(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin]);
    }
}
