<?php

namespace App\Filament\Resources\DeliveryCompanies\Schemas;

use App\Enums\DeliveryProvider;
use App\Filament\Support\Labels;
use App\Services\Delivery\AmeexDeliveryService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DeliveryCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(Labels::section('company'))
                ->schema([
                    TextInput::make('name')->label(Labels::field('name'))->required(),
                    TextInput::make('phone')->label(Labels::field('phone'))->tel(),
                    Select::make('provider')
                        ->label(Labels::field('provider'))
                        ->options(DeliveryProvider::options())
                        ->default(DeliveryProvider::Manual->value)
                        ->required()
                        ->live(),
                    Toggle::make('is_active')->label(Labels::field('is_active'))->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(Labels::section('api'))
                ->schema([
                    TextInput::make('api_base_url')
                        ->label(Labels::field('api_base_url'))
                        ->url()
                        ->default(AmeexDeliveryService::DEFAULT_BASE_URL)
                        ->placeholder(AmeexDeliveryService::DEFAULT_BASE_URL)
                        ->columnSpanFull(),
                    TextInput::make('api_username')
                        ->label('C-Api-Id')
                        ->helperText(__('codflow.delivery.ameex_api_id_help')),
                    TextInput::make('api_token')
                        ->label('C-Api-Key')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(__('codflow.delivery.ameex_api_key_help')),
                ])
                ->columns(2)
                ->visible(fn ($get) => ($get('provider') ?? DeliveryProvider::Manual->value) !== DeliveryProvider::Manual->value)
                ->columnSpanFull(),
            Section::make(__('codflow.delivery.ameex_config_section'))
                ->schema([
                    Select::make('ameex_business_id')
                        ->label(__('codflow.delivery.ameex_business_id'))
                        ->helperText(__('codflow.delivery.ameex_business_id_help'))
                        ->options(function (Get $get): array {
                            $options = $get('ameex_businesses_options');

                            if (! is_array($options)) {
                                $options = [];
                            }

                            $current = trim((string) ($get('ameex_business_id') ?? ''));

                            if (filled($current) && ! array_key_exists($current, $options)) {
                                $options[$current] = $current.' ('.__('codflow.delivery.ameex_business_saved').')';
                            }

                            return $options;
                        })
                        ->searchable()
                        ->columnSpanFull(),
                    Toggle::make('ameex_send_without_stock')
                        ->label(__('codflow.delivery.ameex_send_without_stock'))
                        ->helperText(__('codflow.delivery.ameex_send_without_stock_help'))
                        ->default(false),
                    Placeholder::make('ameex_auto_sync_hint')
                        ->label('')
                        ->content(__('codflow.delivery.ameex_auto_sync_scheduled'))
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn ($get) => ($get('provider') ?? null) === DeliveryProvider::Ameex->value)
                ->columnSpanFull(),
            Section::make(__('codflow.delivery.ameex_advanced_section'))
                ->schema([
                    KeyValue::make('api_settings')
                        ->label(__('codflow.ui.api_settings'))
                        ->keyLabel(__('codflow.ui.key'))
                        ->valueLabel(__('codflow.ui.value'))
                        ->addActionLabel(__('codflow.ui.add'))
                        ->default([
                            'api_id' => '',
                            'default_city_id' => '',
                            'create_parcel_path' => '/customer/Delivery/Parcels/Action/Type/Add',
                            'create_order_path' => '',
                            'products_list_path' => '/customer/Delivery/Products',
                            'track_parcel_path' => AmeexDeliveryService::PATH_MASS_TRACKING,
                            'info_parcel_path' => AmeexDeliveryService::PATH_MASS_INFO,
                            'status_list_path' => AmeexDeliveryService::PATH_STATUS_LIST,
                            'relaunch_parcel_path' => AmeexDeliveryService::PATH_RELAUNCH,
                            'relaunch_new_parcel_path' => AmeexDeliveryService::PATH_RELAUNCH_NEW,
                            'cities_list_path' => '/customer/Delivery/Cities',
                        ])
                        ->columnSpanFull(),
                ])
                ->visible(fn ($get) => ($get('provider') ?? DeliveryProvider::Manual->value) !== DeliveryProvider::Manual->value)
                ->columnSpanFull(),
        ]);
    }
}
