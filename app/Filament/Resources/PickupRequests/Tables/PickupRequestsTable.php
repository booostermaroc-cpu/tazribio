<?php

namespace App\Filament\Resources\PickupRequests\Tables;

use App\Enums\PickupRequestStatus;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PickupRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deliveryCompany.name')->label(Labels::field('carrier')),
                TextColumn::make('requested_date')->date(),
                EnumColumn::badge('status', PickupRequestStatus::class),
            ])
            ->filters([SelectFilter::make('status')->options(PickupRequestStatus::options())])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
