<?php

namespace App\Filament\Resources\BiChecks\Pages;

use App\Filament\Resources\BiChecks\BiCheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBiChecks extends ListRecords
{
    protected static string $resource = BiCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
