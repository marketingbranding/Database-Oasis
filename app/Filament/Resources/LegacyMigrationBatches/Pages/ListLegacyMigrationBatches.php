<?php

namespace App\Filament\Resources\LegacyMigrationBatches\Pages;

use App\Filament\Resources\LegacyMigrationBatches\LegacyMigrationBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegacyMigrationBatches extends ListRecords
{
    protected static string $resource = LegacyMigrationBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
