<?php

namespace App\Filament\Resources\BiChecks\Pages;

use App\Actions\RecordBiCheckAction;
use App\Filament\Resources\BiChecks\BiCheckResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBiCheck extends CreateRecord
{
    protected static string $resource = BiCheckResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::current() ?? abort(403);

        return app(RecordBiCheckAction::class)->handle($user, $data);
    }
}
