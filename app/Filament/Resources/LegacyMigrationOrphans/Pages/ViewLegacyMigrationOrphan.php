<?php

namespace App\Filament\Resources\LegacyMigrationOrphans\Pages;

use App\Filament\Resources\LegacyMigrationOrphans\LegacyMigrationOrphanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLegacyMigrationOrphan extends ViewRecord
{
    protected static string $resource = LegacyMigrationOrphanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
