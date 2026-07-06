<?php

namespace App\Filament\Resources\PaymentPlannings\Pages;

use App\Filament\Resources\PaymentPlannings\PaymentPlanningResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentPlannings extends ListRecords
{
    protected static string $resource = PaymentPlanningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
