<?php

namespace App\Policies;

use App\Models\LegacyMigrationPlan;
use App\Models\User;
use App\UserRole;

class LegacyMigrationPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin, UserRole::Auditor, UserRole::Management]);
    }

    public function view(User $user, LegacyMigrationPlan $plan): bool
    {
        return $this->viewAny($user);
    }

    public function generate(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin]);
    }

    public function simulate(User $user): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin, UserRole::HqAdmin]);
    }
}
