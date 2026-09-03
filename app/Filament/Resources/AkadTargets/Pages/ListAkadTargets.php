<?php

namespace App\Filament\Resources\AkadTargets\Pages;

use App\Filament\Resources\AkadTargets\AkadTargetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAkadTargets extends ListRecords
{
    protected static string $resource = AkadTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
