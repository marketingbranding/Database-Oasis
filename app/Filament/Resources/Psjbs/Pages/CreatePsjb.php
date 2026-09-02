<?php

namespace App\Filament\Resources\Psjbs\Pages;

use App\Actions\CreatePsjbAction;
use App\Filament\Resources\Psjbs\PsjbResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePsjb extends CreateRecord
{
    protected static string $resource = PsjbResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::current() ?? abort(403);

        return app(CreatePsjbAction::class)->handle($user, $data);
    }
}
