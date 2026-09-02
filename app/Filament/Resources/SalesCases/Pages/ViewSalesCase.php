<?php

namespace App\Filament\Resources\SalesCases\Pages;

use App\Filament\Resources\SalesCases\Actions\WorkspaceActions;
use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\Models\SalesCase;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesCase extends ViewRecord
{
    protected static string $resource = SalesCaseResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof SalesCase) {
            return [EditAction::make()->label('Ubah')];
        }

        $primary = WorkspaceActions::primary($record);
        $exclude = $primary !== null ? [$primary->getName()] : [];

        return [
            EditAction::make()->label('Ubah'),
            ...($primary !== null ? [$primary] : []),
            ActionGroup::make(WorkspaceActions::secondary($record, $exclude))
                ->label('Aksi Lainnya')
                ->icon('heroicon-m-ellipsis-horizontal'),
        ];
    }
}
