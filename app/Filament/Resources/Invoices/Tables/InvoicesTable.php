<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Support\EnumColumn;
use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->searchable()->copyable(),
                TextColumn::make('order.order_number')->searchable()->label(Labels::field('order')),
                TextColumn::make('amount')->money('MAD'),
                EnumColumn::badge('status', InvoiceStatus::class),
                TextColumn::make('paid_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')->options(InvoiceStatus::options())])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
