<?php

namespace App\Filament\Resources\DocumentSubmissions\Pages;

use App\Filament\Resources\DocumentSubmissions\DocumentSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentSubmissions extends ListRecords
{
    protected static string $resource = DocumentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
