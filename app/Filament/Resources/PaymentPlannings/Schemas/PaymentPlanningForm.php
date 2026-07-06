<?php

namespace App\Filament\Resources\PaymentPlannings\Schemas;

use App\Enums\PaymentPlanningStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentPlanningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('delivery_company_id')->relationship('deliveryCompany', 'name')->searchable()->preload()->required(),
            TextInput::make('total_amount')->numeric()->prefix('MAD')->required(),
            DatePicker::make('expected_payment_date')->required(),
            Select::make('status')->options(PaymentPlanningStatus::options())->default(PaymentPlanningStatus::Planned->value)->required(),
            DateTimePicker::make('received_at'),
        ]);
    }
}
