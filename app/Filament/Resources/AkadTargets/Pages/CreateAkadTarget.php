<?php

namespace App\Filament\Resources\AkadTargets\Pages;

use App\Filament\Resources\AkadTargets\AkadTargetResource;
use App\Models\Project;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAkadTarget extends CreateRecord
{
    protected static string $resource = AkadTargetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateProjectBranch($data);
        $data['created_by'] = User::current()?->id;
        $data['updated_by'] = User::current()?->id;

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function validateProjectBranch(array $data): void
    {
        if (($data['project_id'] ?? null) !== null && ! Project::query()
            ->whereKey($data['project_id'])
            ->where('branch_id', $data['branch_id'])
            ->exists()) {
            throw ValidationException::withMessages(['project_id' => 'Proyek harus berada pada cabang yang dipilih.']);
        }
    }
}
