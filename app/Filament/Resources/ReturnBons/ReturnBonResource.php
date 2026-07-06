<?php

namespace App\Filament\Resources\ReturnBons;

use App\Filament\Resources\ReturnBons\Pages\CreateReturnBon;
use App\Filament\Resources\ReturnBons\Pages\EditReturnBon;
use App\Filament\Resources\ReturnBons\Pages\ListReturnBons;
use App\Filament\Resources\ReturnBons\Schemas\ReturnBonForm;
use App\Filament\Resources\ReturnBons\Tables\ReturnBonsTable;
use App\Filament\Support\Nav;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Models\ReturnBon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReturnBonResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = ReturnBon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('sales');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('return_bons');
    }

    public static function form(Schema $schema): Schema
    {
        return ReturnBonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnBonsTable::configure($table);
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
            'index' => ListReturnBons::route('/'),
            'create' => CreateReturnBon::route('/create'),
            'edit' => EditReturnBon::route('/{record}/edit'),
        ];
    }
}
