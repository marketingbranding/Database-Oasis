<?php

namespace App\Filament\Resources\BastRecords\Pages;

use App\Actions\CreateBastAction;
use App\Filament\Resources\BastRecords\BastRecordResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBastRecord extends CreateRecord
{
    protected static string $resource = BastRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBastAction::class)->handle(User::current() ?? abort(403), $data);
    }
}
