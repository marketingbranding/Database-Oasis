<?php

namespace App\Policies;

use App\Models\LegacyMigrationBatch;
use App\Models\User;
use App\UserRole;

class LegacyMigrationBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin, UserRole::Auditor, UserRole::Management]);
    }

    public function view(User $user, LegacyMigrationBatch $batch): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin]);
    }
}
