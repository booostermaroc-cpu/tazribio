<?php

namespace App\Filament\Resources\DeliveryCompanies\Pages;

use App\Filament\Resources\DeliveryCompanies\DeliveryCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryCompanies extends ListRecords
{
    protected static string $resource = DeliveryCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
