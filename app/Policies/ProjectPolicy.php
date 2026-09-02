<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\UserRole;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin, UserRole::BranchManager, UserRole::Auditor]);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::Auditor])
            || $user->belongsToBranch($project->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::HqAdmin, UserRole::BranchAdmin]);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasRole(UserRole::HqAdmin)
            || ($user->hasRole(UserRole::BranchAdmin) && $user->belongsToBranch($project->branch_id));
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole(UserRole::HqAdmin);
    }
}
