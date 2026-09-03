<?php

namespace App\Filament\Resources\AkadTargets\Pages;

use App\Filament\Resources\AkadTargets\AkadTargetResource;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAkadTarget extends EditRecord
{
    protected static string $resource = AkadTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['project_id'] ?? null) !== null && ! Project::query()
            ->whereKey($data['project_id'])
            ->where('branch_id', $data['branch_id'])
            ->exists()) {
            throw ValidationException::withMessages(['project_id' => 'Proyek harus berada pada cabang yang dipilih.']);
        }

        $data['updated_by'] = User::current()?->id;

        return $data;
    }
}
