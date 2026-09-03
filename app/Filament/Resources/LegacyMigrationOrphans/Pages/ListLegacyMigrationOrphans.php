<?php

namespace App\Filament\Resources\LegacyMigrationOrphans\Pages;

use App\Filament\Resources\LegacyMigrationOrphans\LegacyMigrationOrphanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegacyMigrationOrphans extends ListRecords
{
    protected static string $resource = LegacyMigrationOrphanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
