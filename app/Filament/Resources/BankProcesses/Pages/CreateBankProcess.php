<?php

namespace App\Filament\Resources\BankProcesses\Pages;

use App\Actions\RecordBankResponseAction;
use App\Filament\Resources\BankProcesses\BankProcessResource;
use App\Models\DocumentSubmission;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBankProcess extends CreateRecord
{
    protected static string $resource = BankProcessResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $submission = DocumentSubmission::findOrFail($data['document_submission_id']);
        $data['sales_case_id'] = $submission->sales_case_id;
        $data['bank_id'] = $submission->bank_id;

        return app(RecordBankResponseAction::class)->handle(User::current() ?? abort(403), $data);
    }
}
