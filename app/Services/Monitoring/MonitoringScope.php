<?php

namespace App\Services\Monitoring;

use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Validation\ValidationException;

final class MonitoringScope
{
    public function __construct(
        public User $user,
        public ?string $branchId = null,
        public ?string $projectId = null,
        private bool $strict = true,
    ) {
        if ($user->hasAnyRole([UserRole::BranchAdmin, UserRole::BranchManager]) && $user->branch_id === null) {
            throw ValidationException::withMessages(['branch_id' => 'Akun cabang belum memiliki cabang.']);
        }

        if ($user->isBranchScoped() && $branchId !== null && ! $user->belongsToBranch($branchId)) {
            if ($this->strict) {
                throw ValidationException::withMessages(['branch_id' => 'Cabang tidak dapat diakses.']);
            }

            $this->branchId = null;
        }

        if ($projectId !== null && ! $this->projectAccessible($projectId)) {
            if ($this->strict) {
                throw ValidationException::withMessages(['project_id' => 'Proyek tidak dapat diakses.']);
            }

            $this->projectId = null;
        }
    }

    public function branchId(): ?string
    {
        return $this->user->isBranchScoped() ? $this->user->branch_id : $this->branchId;
    }

    private function projectAccessible(string $projectId): bool
    {
        return Project::query()
            ->whereKey($projectId)
            ->when($this->branchId() !== null, fn ($query) => $query->where('branch_id', $this->branchId()))
            ->exists();
    }
}
