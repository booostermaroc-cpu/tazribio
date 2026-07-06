<?php

namespace App\Filament\Resources\PickupRequests;

use App\Filament\Resources\PickupRequests\Pages\CreatePickupRequest;
use App\Filament\Resources\PickupRequests\Pages\EditPickupRequest;
use App\Filament\Resources\PickupRequests\Pages\ListPickupRequests;
use App\Filament\Resources\PickupRequests\Schemas\PickupRequestForm;
use App\Filament\Resources\PickupRequests\Tables\PickupRequestsTable;
use App\Filament\Support\Nav;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Models\PickupRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PickupRequestResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = PickupRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('sales');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('pickup_requests');
    }

    public static function form(Schema $schema): Schema
    {
        return PickupRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PickupRequestsTable::configure($table);
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
            'index' => ListPickupRequests::route('/'),
            'create' => CreatePickupRequest::route('/create'),
            'edit' => EditPickupRequest::route('/{record}/edit'),
        ];
    }
}
