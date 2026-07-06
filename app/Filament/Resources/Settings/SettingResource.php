<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\ManageSetting;
use App\Filament\Resources\Settings\Schemas\SettingForm;
use App\Filament\Support\Nav;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Models\Setting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SettingResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = Setting::class;

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('system');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('settings');
    }

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSetting::route('/'),
        ];
    }
}
