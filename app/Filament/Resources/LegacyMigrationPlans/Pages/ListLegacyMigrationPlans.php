<?php

namespace App\Filament\Resources\LegacyMigrationPlans\Pages;

use App\Filament\Resources\LegacyMigrationPlans\LegacyMigrationPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegacyMigrationPlans extends ListRecords
{
    protected static string $resource = LegacyMigrationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
