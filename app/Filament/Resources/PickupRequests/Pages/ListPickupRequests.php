<?php

namespace App\Filament\Resources\PickupRequests\Pages;

use App\Filament\Resources\PickupRequests\PickupRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPickupRequests extends ListRecords
{
    protected static string $resource = PickupRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
