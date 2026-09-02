<?php

namespace App\Filament\Resources\BastRecords\Pages;

use App\Filament\Resources\BastRecords\BastRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBastRecords extends ListRecords
{
    protected static string $resource = BastRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
