<?php

namespace App\Filament\Resources\DeveloperPpjbs\Pages;

use App\Filament\Resources\DeveloperPpjbs\DeveloperPpjbResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeveloperPpjbs extends ListRecords
{
    protected static string $resource = DeveloperPpjbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
