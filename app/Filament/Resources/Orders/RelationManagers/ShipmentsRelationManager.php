<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('codflow.relations.shipments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('delivery_company_id')
                    ->relationship('deliveryCompany', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('tracking_number')
                    ->default(fn () => 'TRK-'.strtoupper(Str::random(10)))
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('delivery_status')
                    ->options(ShipmentStatus::options())
                    ->default(ShipmentStatus::Pending->value)
                    ->required(),
                DatePicker::make('delivery_date'),
                Textarea::make('return_reason'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_number')
                    ->searchable()
                    ->copyable()
                    ->url(fn ($record): string => ShipmentResource::getUrl('view', ['record' => $record])),
                TextColumn::make('deliveryCompany.name')->label(Labels::field('carrier')),
                EnumColumn::badge('delivery_status', ShipmentStatus::class),
                TextColumn::make('delivery_date')->date(),
                TextColumn::make('last_tracking_update')->dateTime(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
