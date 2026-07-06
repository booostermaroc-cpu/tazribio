<?php

namespace App\Filament\Resources\PaymentPlannings\Pages;

use App\Filament\Resources\PaymentPlannings\PaymentPlanningResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentPlanning extends EditRecord
{
    protected static string $resource = PaymentPlanningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
