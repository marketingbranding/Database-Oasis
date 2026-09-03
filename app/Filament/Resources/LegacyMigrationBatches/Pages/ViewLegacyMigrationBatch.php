<?php

namespace App\Filament\Resources\LegacyMigrationBatches\Pages;

use App\Filament\Resources\LegacyMigrationBatches\LegacyMigrationBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLegacyMigrationBatch extends ViewRecord
{
    protected static string $resource = LegacyMigrationBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
