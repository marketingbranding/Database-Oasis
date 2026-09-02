<?php

namespace App\Filament\Resources\AkadRecords\Pages;

use App\Actions\CreateAkadAction;
use App\Filament\Resources\AkadRecords\AkadRecordResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAkadRecord extends CreateRecord
{
    protected static string $resource = AkadRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateAkadAction::class)->handle(User::current() ?? abort(403), $data);
    }
}
