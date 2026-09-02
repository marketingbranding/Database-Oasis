<?php

namespace App\Filament\Resources\SalesCases\Pages;

use App\Filament\Resources\SalesCases\Actions\CaseWorkflowActions;
use App\Filament\Resources\SalesCases\SalesCaseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesCase extends ViewRecord
{
    protected static string $resource = SalesCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah'),
            CaseWorkflowActions::mundur(),
            CaseWorkflowActions::reject(),
            CaseWorkflowActions::cancel(),
            CaseWorkflowActions::move(),
        ];
    }
}
