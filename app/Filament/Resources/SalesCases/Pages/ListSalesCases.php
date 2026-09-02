<?php

namespace App\Filament\Resources\SalesCases\Pages;

use App\Filament\Resources\SalesCases\SalesCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesCases extends ListRecords
{
    protected static string $resource = SalesCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
