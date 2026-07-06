<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Filament\Support\EnumColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label(__('codflow.products.image'))
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=P&background=ede9fe&color=7c3aed'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable()->copyable(),
                TextColumn::make('purchase_price')->money('MAD')->sortable(),
                TextColumn::make('selling_price')->money('MAD')->sortable(),
                TextColumn::make('current_stock')
                    ->sortable()
                    ->color(fn ($record) => $record->isLowStock() ? 'danger' : null),
                TextColumn::make('stock_alert')->sortable(),
                EnumColumn::badge('status', ProductStatus::class),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')->options(ProductStatus::options()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
