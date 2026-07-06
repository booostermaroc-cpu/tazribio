<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Support\Labels;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(Labels::field('name'))->required()->maxLength(191),
                TextInput::make('sku')
                    ->label(Labels::field('sku'))
                    ->helperText(__('codflow.products.sku_ameex_help'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                FileUpload::make('image')
                    ->label(__('codflow.products.image'))
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')
                    ->imageEditor()
                    ->imagePreviewHeight('160')
                    ->maxSize(2048),
                TextInput::make('purchase_price')->numeric()->prefix('MAD')->required(),
                TextInput::make('selling_price')->numeric()->prefix('MAD')->required(),
                TextInput::make('current_stock')->numeric()->default(0)->required(),
                TextInput::make('stock_alert')->numeric()->default(5)->required(),
                Select::make('status')
                    ->options(ProductStatus::options())
                    ->default(ProductStatus::Active->value)
                    ->required(),
            ]);
    }
}
