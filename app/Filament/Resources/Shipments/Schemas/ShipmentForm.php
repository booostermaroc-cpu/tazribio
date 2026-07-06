<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Enums\ShipmentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('order_id')->relationship('order', 'order_number')->searchable()->preload()->required(),
            Select::make('delivery_company_id')->relationship('deliveryCompany', 'name')->searchable()->preload()->required(),
            TextInput::make('tracking_number')->default(fn () => 'TRK-'.strtoupper(Str::random(10)))->required()->unique(ignoreRecord: true),
            TextInput::make('ameex_delivery_note_ref')
                ->label(__('codflow.fields.ameex_delivery_note_ref'))
                ->maxLength(191),
            TextInput::make('ameex_parcel_code')
                ->label(__('codflow.ui.ameex_parcel_code'))
                ->maxLength(191),
            TextInput::make('ameex_last_status_name')
                ->label(__('codflow.ui.ameex_last_status'))
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit'),
            Select::make('delivery_status')->options(ShipmentStatus::options())->default(ShipmentStatus::Pending->value)->required(),
            DatePicker::make('delivery_date'),
            Textarea::make('return_reason'),
        ]);
    }
}
