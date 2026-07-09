<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Enums\CarrierFeeTrigger;
use App\Enums\CommissionApplyOn;
use App\Enums\CommissionType;
use App\Enums\PaymentMethod;
use App\Filament\Support\Labels;
use App\Rules\MoroccanPhone;
use App\Services\CarrierFeeService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(Labels::section('company'))
                    ->schema([
                        TextInput::make('company_name')
                            ->label(Labels::field('company_name'))
                            ->required()
                            ->maxLength(191),
                        FileUpload::make('logo')
                            ->label(Labels::field('logo'))
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label(Labels::field('phone'))
                            ->tel()
                            ->rules([new MoroccanPhone]),
                        Textarea::make('address')
                            ->label(Labels::field('address'))
                            ->columnSpanFull(),
                        TextInput::make('rib')
                            ->label(Labels::field('rib'))
                            ->maxLength(191),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('codflow.sections.carrier_fees'))
                    ->description(__('codflow.settings.carrier_fees_help'))
                    ->schema([
                        Repeater::make('carrier_fee_rules')
                            ->label(__('codflow.settings.carrier_fee_rules'))
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('codflow.settings.carrier_fee_label'))
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('amount')
                                    ->label(__('codflow.settings.carrier_fee_amount'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('MAD')
                                    ->required(),
                                Select::make('trigger')
                                    ->label(__('codflow.settings.carrier_fee_trigger'))
                                    ->options(CarrierFeeTrigger::options())
                                    ->required(),
                                TextInput::make('key')
                                    ->label(__('codflow.ui.key'))
                                    ->maxLength(64)
                                    ->helperText(__('codflow.settings.carrier_fee_key_help')),
                            ])
                            ->columns(2)
                            ->default(CarrierFeeService::defaultRules())
                            ->addActionLabel(__('codflow.settings.carrier_fee_add'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(Labels::section('defaults'))
                    ->schema([
                        TextInput::make('default_delivery_fee')
                            ->label(Labels::field('default_delivery_fee'))
                            ->numeric()
                            ->prefix('MAD')
                            ->minValue(0)
                            ->default(15),
                        Select::make('default_payment_method')
                            ->label(Labels::field('default_payment_method'))
                            ->options(PaymentMethod::options())
                            ->default(PaymentMethod::Cod->value)
                            ->required(),
                        Select::make('default_delivery_company_id')
                            ->label(Labels::field('default_delivery_company'))
                            ->relationship('defaultDeliveryCompany', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('order_prefix')
                            ->label(Labels::field('order_prefix'))
                            ->required()
                            ->maxLength(20)
                            ->default('ORD'),
                        TextInput::make('invoice_prefix')
                            ->label(Labels::field('invoice_prefix'))
                            ->required()
                            ->maxLength(20)
                            ->default('INV'),
                        TextInput::make('return_bon_prefix')
                            ->label(Labels::field('return_bon_prefix'))
                            ->required()
                            ->maxLength(20)
                            ->default('RET'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make(Labels::section('commissions_defaults'))
                    ->schema([
                        Select::make('agent_commission_default_type')
                            ->label(Labels::field('commission_type'))
                            ->options(CommissionType::options())
                            ->default(CommissionType::None->value)
                            ->required(),
                        TextInput::make('agent_commission_default_value')
                            ->label(Labels::field('commission_value'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Select::make('agent_commission_apply_on')
                            ->label(Labels::field('commission_apply_on'))
                            ->options(CommissionApplyOn::options())
                            ->default(CommissionApplyOn::Delivered->value)
                            ->required(),
                        Toggle::make('profit_include_delivery_fee')
                            ->label(__('codflow.ui.include_delivery_in_profit'))
                            ->default(true),
                        Toggle::make('use_manual_profit_total')
                            ->label(__('codflow.settings.use_manual_profit_total'))
                            ->helperText(__('codflow.settings.use_manual_profit_total_hint'))
                            ->live()
                            ->default(false),
                        TextInput::make('manual_profit_total')
                            ->label(__('codflow.settings.manual_profit_total'))
                            ->numeric()
                            ->prefix('MAD')
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => (bool) $get('use_manual_profit_total'))
                            ->helperText(__('codflow.settings.manual_profit_total_hint')),
                        TextInput::make('carrier_stuck_days')
                            ->label(__('codflow.fields.carrier_stuck_days'))
                            ->numeric()
                            ->minValue(1)
                            ->default(60)
                            ->required()
                            ->helperText(__('codflow.fields.carrier_stuck_days_help')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
