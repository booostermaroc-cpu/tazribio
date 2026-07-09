<?php

namespace App\Filament\Resources\DeliveryCompanies\Schemas;

use App\Enums\DeliveryProvider;
use App\Filament\Support\Labels;
use App\Services\Delivery\AmeexDeliveryService;
use Filament\Forms\Components\Hidden;
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
                ->visible(fn (Get $get): bool => self::isApiProvider($get('provider')))
                ->columnSpanFull(),
            Section::make(__('codflow.delivery.ameex_config_section'))
                ->schema([
                    Hidden::make('ameex_businesses_options_json')
                        ->dehydrated(false),
                    Hidden::make('ameex_cities_count')
                        ->dehydrated(false),
                    Select::make('ameex_business_id')
                        ->label(__('codflow.delivery.ameex_business_id'))
                        ->helperText(__('codflow.delivery.ameex_business_id_help'))
                        ->options(function (Get $get): array {
                            $decoded = json_decode((string) ($get('ameex_businesses_options_json') ?? '{}'), true);
                            $options = is_array($decoded) ? $decoded : [];

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
                    Placeholder::make('ameex_sync_summary')
                        ->label(__('codflow.delivery.ameex_sync_summary'))
                        ->content(function (Get $get): string {
                            $decoded = json_decode((string) ($get('ameex_businesses_options_json') ?? '{}'), true);
                            $options = is_array($decoded) ? $decoded : [];
                            $cities = (int) ($get('ameex_cities_count') ?? 0);

                            return __('codflow.delivery.ameex_sync_summary_text', [
                                'hubs' => count($options),
                                'cities' => $cities,
                            ]);
                        })
                        ->columnSpanFull(),
                    Placeholder::make('ameex_auto_sync_hint')
                        ->content(__('codflow.delivery.ameex_auto_sync_scheduled'))
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn (Get $get): bool => self::isAmeexProvider($get('provider')))
                ->columnSpanFull(),
        ]);
    }

    protected static function isApiProvider(mixed $provider): bool
    {
        if ($provider instanceof DeliveryProvider) {
            return $provider !== DeliveryProvider::Manual;
        }

        return filled($provider) && (string) $provider !== DeliveryProvider::Manual->value;
    }

    protected static function isAmeexProvider(mixed $provider): bool
    {
        if ($provider instanceof DeliveryProvider) {
            return $provider === DeliveryProvider::Ameex;
        }

        return (string) $provider === DeliveryProvider::Ameex->value;
    }
}
