<?php

namespace App\Filament\Resources\DeliveryCompanies;

use App\Filament\Resources\DeliveryCompanies\Pages\CreateDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\EditDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\ListDeliveryCompanies;
use App\Filament\Resources\DeliveryCompanies\Schemas\DeliveryCompanyForm;
use App\Filament\Resources\DeliveryCompanies\Tables\DeliveryCompaniesTable;
use App\Filament\Support\Nav;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Models\DeliveryCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryCompanyResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = DeliveryCompany::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('sales');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('delivery_companies');
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryCompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryCompaniesTable::configure($table);
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
            'index' => ListDeliveryCompanies::route('/'),
            'create' => CreateDeliveryCompany::route('/create'),
            'edit' => EditDeliveryCompany::route('/{record}/edit'),
        ];
    }
}
