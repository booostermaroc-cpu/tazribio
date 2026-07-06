<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Filament\Support\Labels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('codflow.relations.items');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->selling_price);
                            }
                        }
                    }),
                TextInput::make('quantity')->numeric()->default(1)->required()->minValue(1)->live(),
                TextInput::make('unit_price')->numeric()->required()->minValue(0)->live(),
                TextInput::make('total_price')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->afterStateHydrated(function (TextInput $component, $state, callable $get, callable $set) {
                        $set('total_price', ($get('quantity') ?? 0) * ($get('unit_price') ?? 0));
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->searchable(),
                TextColumn::make('product.sku')->label(Labels::field('sku')),
                TextColumn::make('quantity'),
                TextColumn::make('unit_price')->money('MAD'),
                TextColumn::make('total_price')->money('MAD'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
