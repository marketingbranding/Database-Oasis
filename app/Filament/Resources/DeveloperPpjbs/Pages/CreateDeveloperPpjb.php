<?php

namespace App\Filament\Resources\DeveloperPpjbs\Pages;

use App\Actions\CreateDeveloperPpjbAction;
use App\Filament\Resources\DeveloperPpjbs\DeveloperPpjbResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeveloperPpjb extends CreateRecord
{
    protected static string $resource = DeveloperPpjbResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateDeveloperPpjbAction::class)->handle(User::current() ?? abort(403), $data);
    }
}
