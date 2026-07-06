<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Enums\StockMovementType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->required(),
            Select::make('warehouse_id')->relationship('warehouse', 'name')->searchable()->preload()->required(),
            Select::make('type')->options(StockMovementType::options())->required(),
            TextInput::make('quantity')->numeric()->required(),
            TextInput::make('reason'),
            Select::make('user_id')->relationship('user', 'name')->searchable()->preload()->default(fn () => auth()->id())->required(),
        ]);
    }
}
