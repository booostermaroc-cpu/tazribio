<?php

namespace App\Filament\Resources\PickupRequests\Pages;

use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Filament\Resources\PickupRequests\Schemas\PickupRequestForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePickupRequest extends CreateRecord
{
    protected static string $resource = PickupRequestResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PickupRequestForm::normalizeAmeexPickupData($data);
    }
}
