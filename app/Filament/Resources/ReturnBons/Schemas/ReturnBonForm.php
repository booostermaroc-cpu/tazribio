<?php

namespace App\Filament\Resources\ReturnBons\Schemas;

use App\Enums\ReturnBonStatus;
use App\Filament\Support\Labels;
use App\Services\ReturnScanService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReturnBonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('order_id')
                ->label(Labels::field('order'))
                ->relationship('order', 'order_number')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set): void {
                    if ($state) {
                        $order = \App\Models\Order::query()->find($state);
                        if ($order) {
                            $set('barcode_token', $order->order_number);
                        }
                    }
                }),
            TextInput::make('return_number')
                ->label(__('codflow.ui.return_number'))
                ->default(fn () => app(ReturnScanService::class)->generateReturnNumber())
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('barcode_token')
                ->label(__('codflow.ui.barcode_qr'))
                ->helperText(__('codflow.ui.barcode_help'))
                ->maxLength(191),
            Textarea::make('reason')->label(Labels::field('notes'))->required(),
            Toggle::make('with_packaging')
                ->label(__('codflow.fields.return_with_packaging'))
                ->helperText(__('codflow.fields.return_with_packaging_help'))
                ->default(false),
            Select::make('status')
                ->label(Labels::field('status'))
                ->options(ReturnBonStatus::options())
                ->default(ReturnBonStatus::Requested->value)
                ->required(),
        ]);
    }
}
