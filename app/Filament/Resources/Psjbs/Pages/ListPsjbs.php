<?php

namespace App\Filament\Resources\Psjbs\Pages;

use App\Filament\Resources\Psjbs\PsjbResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPsjbs extends ListRecords
{
    protected static string $resource = PsjbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
