<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Project $record */
        $record = $this->record;

        $newBranchId = $data['branch_id'] ?? null;

        if ($record->salesCases()->exists() && $newBranchId !== null && $newBranchId !== $record->branch_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'Proyek memiliki data transaksi dan tidak dapat dipindahkan ke cabang lain.',
            ]);
        }

        return $data;
    }
}
