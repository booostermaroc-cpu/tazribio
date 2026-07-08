<?php

namespace App\Filament\Resources\DeliveryCompanies\Pages;

use App\Filament\Resources\DeliveryCompanies\Concerns\InteractsWithAmeexBusinessIdForm;
use App\Filament\Resources\DeliveryCompanies\DeliveryCompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryCompany extends CreateRecord
{
    use InteractsWithAmeexBusinessIdForm;

    protected static string $resource = DeliveryCompanyResource::class;
}
