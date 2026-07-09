<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Support\Labels;
use App\Filament\Support\OrderConfirmationSection;
use App\Filament\Support\OrderFulfillmentSection;
use App\Models\Product;
use App\Models\Client;
use App\Services\OrderCalculationService;
use App\Services\OrderProfitService;
use App\Services\SettingService;
use App\Rules\MoroccanPhone;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                OrderConfirmationSection::make(),
                OrderFulfillmentSection::make(),
                Section::make(__('codflow.order.section_client'))
                    ->schema([
                        Select::make('client_id')
                            ->label(fn (string $operation): string => $operation === 'create'
                                ? __('codflow.order.existing_client')
                                : Labels::field('client'))
                            ->relationship('client', 'full_name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn (string $operation): bool => $operation === 'edit')
                            ->helperText(fn (string $operation): ?string => $operation === 'create'
                                ? __('codflow.order.existing_client_hint')
                                : null)
                            ->afterStateUpdated(function ($state, Set $set, Get $get, string $operation): void {
                                if (! $state) {
                                    return;
                                }

                                $client = Client::query()->find($state);

                                if (! $client) {
                                    return;
                                }

                                if ($operation === 'create') {
                                    $set('client_full_name', $client->full_name);
                                    $set('client_phone', $client->phone);
                                    $set('client_second_phone', $client->second_phone);
                                    $set('city', $client->city);
                                    $set('address', $client->address);

                                    return;
                                }

                                if (blank($get('city')) && filled($client->city)) {
                                    $set('city', $client->city);
                                }

                                if (blank($get('address')) && filled($client->address)) {
                                    $set('address', $client->address);
                                }
                            }),
                        TextInput::make('client_full_name')
                            ->label(Labels::field('full_name'))
                            ->maxLength(191)
                            ->dehydrated(false)
                            ->required(fn (string $operation, Get $get): bool => $operation === 'create' && blank($get('client_id')))
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('client_phone')
                            ->label(Labels::field('phone'))
                            ->tel()
                            ->maxLength(191)
                            ->rules([new MoroccanPhone])
                            ->dehydrated(false)
                            ->required(fn (string $operation, Get $get): bool => $operation === 'create' && blank($get('client_id')))
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('client_second_phone')
                            ->label(Labels::field('second_phone'))
                            ->tel()
                            ->maxLength(191)
                            ->rules([new MoroccanPhone])
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(Labels::section('order'))
                    ->schema([
                        TextInput::make('order_number')
                            ->label(Labels::field('order_number'))
                            ->default(fn () => SettingService::get()->order_prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191),
                        Select::make('status')
                            ->label(Labels::field('status'))
                            ->options(OrderStatus::options())
                            ->default(OrderStatus::New->value)
                            ->required(),
                        Select::make('source')
                            ->label(Labels::field('source'))
                            ->options(OrderSource::options())
                            ->default(OrderSource::Other->value)
                            ->required(),
                        TextInput::make('city')->label(Labels::field('city'))->required()->maxLength(191),
                        Textarea::make('address')
                            ->label(Labels::field('address'))
                            ->required()
                            ->helperText(__('codflow.delivery.ameex_address_required'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(Labels::section('order_items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label(Labels::section('order_items'))
                            ->schema([
                                Select::make('product_id')
                                    ->label(Labels::field('product'))
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                        if ($state) {
                                            $product = Product::query()->find($state);
                                            if ($product) {
                                                $set('unit_price', $product->selling_price);
                                                $qty = (float) ($get('quantity') ?: 1);
                                                $set('total_price', app(OrderCalculationService::class)->calculateLineTotal($qty, (float) $product->selling_price));
                                            }
                                        }
                                    }),
                                TextInput::make('quantity')
                                    ->label(Labels::field('quantity'))
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                        $set('total_price', app(OrderCalculationService::class)->calculateLineTotal(
                                            (float) ($state ?? 0),
                                            (float) ($get('unit_price') ?? 0),
                                        ));
                                    }),
                                TextInput::make('unit_price')
                                    ->label(Labels::field('unit_price'))
                                    ->numeric()
                                    ->prefix('MAD')
                                    ->disabled()
                                    ->dehydrated()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                        $set('total_price', app(OrderCalculationService::class)->calculateLineTotal(
                                            (float) ($get('quantity') ?? 0),
                                            (float) ($state ?? 0),
                                        ));
                                    }),
                                TextInput::make('total_price')
                                    ->label(Labels::field('line_total'))
                                    ->numeric()
                                    ->prefix('MAD')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateOrderTotals($get, $set))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(Labels::section('amounts'))
                    ->schema([
                        TextInput::make('total_amount')
                            ->label(Labels::field('total_amount'))
                            ->numeric()
                            ->prefix('MAD')
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        TextInput::make('delivery_fee')
                            ->label(Labels::field('delivery_fee'))
                            ->numeric()
                            ->prefix('MAD')
                            ->default(15)
                            ->helperText(__('codflow.order.delivery_fee_hint'))
                            ->minValue(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateOrderTotals($get, $set)),
                        TextInput::make('discount')
                            ->label(Labels::field('discount'))
                            ->numeric()
                            ->prefix('MAD')
                            ->default(0)
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateOrderTotals($get, $set)),
                        TextInput::make('final_amount')
                            ->label(Labels::field('final_amount'))
                            ->numeric()
                            ->prefix('MAD')
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        TextInput::make('carrier_cod_preview')
                            ->label(__('codflow.order.carrier_cod_amount'))
                            ->numeric()
                            ->prefix('MAD')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0)
                            ->helperText(__('codflow.order.carrier_cod_amount_hint')),
                        Toggle::make('profit_is_manual')
                            ->label(__('codflow.order.profit_manual'))
                            ->helperText(__('codflow.order.profit_manual_hint'))
                            ->live()
                            ->default(false)
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method')) !== PaymentMethod::Cod)
                            ->columnSpanFull(),
                        TextInput::make('profit_amount')
                            ->label(__('codflow.order.profit_amount'))
                            ->numeric()
                            ->prefix('MAD')
                            ->minValue(0)
                            ->disabled(fn (Get $get): bool => ! (bool) $get('profit_is_manual'))
                            ->dehydrated()
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method')) !== PaymentMethod::Cod)
                            ->helperText(fn (Get $get, $record): string => (bool) $get('profit_is_manual')
                                ? __('codflow.order.profit_manual_active_hint')
                                : __('codflow.order.profit_auto_hint'))
                            ->placeholder(fn ($record): ?string => $record instanceof \App\Models\Order
                                ? (string) app(OrderProfitService::class)->calculateAuto($record)
                                : null),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),
                Section::make(Labels::section('payment'))
                    ->schema([
                        Select::make('payment_method')
                            ->label(Labels::field('payment_method'))
                            ->options(PaymentMethod::options())
                            ->default(fn () => SettingService::get()->default_payment_method ?? PaymentMethod::Cod->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (PaymentMethod::tryFrom((string) $state) === PaymentMethod::Cod) {
                                    $set('profit_is_manual', false);
                                    $set('profit_amount', 0);
                                }
                            }),
                        Select::make('payment_status')
                            ->label(Labels::field('payment_status'))
                            ->options(PaymentStatus::options())
                            ->default(PaymentStatus::Unpaid->value)
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label(Labels::field('payment_reference'))
                            ->maxLength(191)
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresPaymentDetails() ?? false),
                        TextInput::make('payment_receiver_name')
                            ->label(Labels::field('payment_receiver_name'))
                            ->maxLength(191)
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresPaymentDetails() ?? false),
                        TextInput::make('payment_receiver_rib')
                            ->label(Labels::field('payment_receiver_rib'))
                            ->maxLength(191)
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresPaymentDetails() ?? false),
                        FileUpload::make('payment_receipt')
                            ->label(Labels::field('payment_receipt'))
                            ->disk('public')
                            ->directory('payment-receipts')
                            ->visibility('public')
                            ->image()
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresPaymentDetails() ?? false),
                        DateTimePicker::make('payment_received_at')
                            ->label(Labels::field('payment_received_at'))
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresPaymentDetails() ?? false),
                        Textarea::make('payment_notes')
                            ->label(Labels::field('payment_notes'))
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresPaymentDetails() ?? false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label(Labels::field('notes'))
                    ->columnSpanFull(),
            ]);
    }

    protected static function recalculateOrderTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $totals = app(OrderCalculationService::class)->calculateTotals(
            is_array($items) ? $items : [],
            (float) ($get('delivery_fee') ?? 0),
            (float) ($get('discount') ?? 0),
        );

        $set('total_amount', $totals['total_amount']);
        $set('final_amount', $totals['final_amount']);
        $set('carrier_cod_preview', $totals['carrier_cod_amount']);
    }
}
