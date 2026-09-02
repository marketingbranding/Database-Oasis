<?php

namespace App\Filament\Resources\AkadRecords\Pages;

use App\Filament\Resources\AkadRecords\AkadRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAkadRecords extends ListRecords
{
    protected static string $resource = AkadRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
