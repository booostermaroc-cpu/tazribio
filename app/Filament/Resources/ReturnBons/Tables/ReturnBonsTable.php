<?php

namespace App\Filament\Resources\ReturnBons\Tables;

use App\Enums\ReturnBonStatus;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReturnBonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')->searchable()->copyable(),
                TextColumn::make('order.order_number')->searchable()->label(Labels::field('order')),
                EnumColumn::badge('status', ReturnBonStatus::class),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')->options(ReturnBonStatus::options())])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
