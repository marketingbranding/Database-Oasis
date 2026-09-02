<?php

namespace App\Filament\Resources\DocumentSubmissions\Pages;

use App\Actions\CreateDocumentSubmissionAction;
use App\Filament\Resources\DocumentSubmissions\DocumentSubmissionResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDocumentSubmission extends CreateRecord
{
    protected static string $resource = DocumentSubmissionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateDocumentSubmissionAction::class)->handle(User::current() ?? abort(403), $data);
    }
}
