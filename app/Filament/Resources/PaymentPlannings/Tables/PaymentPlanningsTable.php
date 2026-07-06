<?php

namespace App\Filament\Resources\PaymentPlannings\Tables;

use App\Enums\PaymentPlanningStatus;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentPlanningsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deliveryCompany.name')->label(Labels::field('carrier')),
                TextColumn::make('total_amount')->money('MAD'),
                TextColumn::make('expected_payment_date')->date(),
                EnumColumn::badge('status', PaymentPlanningStatus::class),
                TextColumn::make('received_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')->options(PaymentPlanningStatus::options())])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
