<?php

namespace App\Filament\Resources\Shipments;

use App\Filament\Resources\Shipments\Pages\CreateShipment;
use App\Filament\Resources\Shipments\Pages\EditShipment;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use App\Filament\Resources\Shipments\Schemas\ShipmentForm;
use App\Filament\Resources\Shipments\Schemas\ShipmentInfolist;
use App\Filament\Resources\Shipments\Tables\ShipmentsTable;
use App\Filament\Support\Nav;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Models\Shipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShipmentResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('sales');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('shipments');
    }

    public static function form(Schema $schema): Schema
    {
        return ShipmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShipmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShipmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'create' => CreateShipment::route('/create'),
            'view' => ViewShipment::route('/{record}'),
            'edit' => EditShipment::route('/{record}/edit'),
        ];
    }
}
