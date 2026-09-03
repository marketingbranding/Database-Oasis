<?php

namespace App\Filament\Resources\LegacyMigrationCandidates\Pages;

use App\Filament\Resources\LegacyMigrationCandidates\LegacyMigrationCandidateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegacyMigrationCandidates extends ListRecords
{
    protected static string $resource = LegacyMigrationCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
