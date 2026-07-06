<?php

namespace App\Filament\Resources\PaymentPlannings;

use App\Filament\Resources\PaymentPlannings\Pages\CreatePaymentPlanning;
use App\Filament\Resources\PaymentPlannings\Pages\EditPaymentPlanning;
use App\Filament\Resources\PaymentPlannings\Pages\ListPaymentPlannings;
use App\Filament\Resources\PaymentPlannings\Schemas\PaymentPlanningForm;
use App\Filament\Resources\PaymentPlannings\Tables\PaymentPlanningsTable;
use App\Filament\Support\Nav;
use App\Filament\Support\HasCodflowResourceLabels;
use App\Models\PaymentPlanning;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentPlanningResource extends Resource
{
    use HasCodflowResourceLabels;

    protected static ?string $model = PaymentPlanning::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return Nav::group('finance');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('payment_plannings');
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentPlanningForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentPlanningsTable::configure($table);
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
            'index' => ListPaymentPlannings::route('/'),
            'create' => CreatePaymentPlanning::route('/create'),
            'edit' => EditPaymentPlanning::route('/{record}/edit'),
        ];
    }
}
