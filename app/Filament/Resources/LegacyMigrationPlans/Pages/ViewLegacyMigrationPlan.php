<?php

namespace App\Filament\Resources\LegacyMigrationPlans\Pages;

use App\Filament\Resources\LegacyMigrationPlans\LegacyMigrationPlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLegacyMigrationPlan extends ViewRecord
{
    protected static string $resource = LegacyMigrationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
