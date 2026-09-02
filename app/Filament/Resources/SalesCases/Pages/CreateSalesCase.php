<?php

namespace App\Filament\Resources\SalesCases\Pages;

use App\Actions\CreateSalesCaseAction;
use App\Filament\Resources\SalesCases\SalesCaseResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesCase extends CreateRecord
{
    protected static string $resource = SalesCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['create_new_consumer'])) {
            $data['consumer_attributes'] = [
                'nik' => $data['new_consumer_nik'] ?? null,
                'name' => $data['new_consumer_name'] ?? null,
                'phone' => $data['new_consumer_phone'] ?? null,
            ];
            unset($data['consumer_id']);
        }

        unset($data['create_new_consumer'], $data['new_consumer_nik'], $data['new_consumer_name'], $data['new_consumer_phone']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::current() ?? abort(403);

        return app(CreateSalesCaseAction::class)->handle($user, $data);
    }
}
