<?php

namespace App\Filament\Resources\BankProcesses\Pages;

use App\Filament\Resources\BankProcesses\BankProcessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankProcesses extends ListRecords
{
    protected static string $resource = BankProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
