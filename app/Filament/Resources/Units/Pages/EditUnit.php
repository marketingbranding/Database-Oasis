<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Unit $record */
        $record = $this->record;

        $newProjectId = $data['project_id'] ?? null;

        if ($record->salesCases()->exists() && $newProjectId !== null && $newProjectId !== $record->project_id) {
            throw ValidationException::withMessages([
                'project_id' => 'Unit memiliki histori sales case dan tidak dapat dipindahkan ke proyek lain.',
            ]);
        }

        return $data;
    }
}
