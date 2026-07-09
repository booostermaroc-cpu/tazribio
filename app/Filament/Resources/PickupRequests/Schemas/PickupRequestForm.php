<?php

namespace App\Filament\Resources\PickupRequests\Schemas;

use App\Enums\DeliveryProvider;
use App\Enums\PickupRequestStatus;
use App\Filament\Support\Labels;
use App\Models\DeliveryCompany;
use App\Services\Delivery\AmeexDeliveryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PickupRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(Labels::section('order'))
                ->schema([
                    Select::make('delivery_company_id')
                        ->label(Labels::field('delivery_company'))
                        ->relationship('deliveryCompany', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    DatePicker::make('requested_date')
                        ->label(Labels::field('requested_date'))
                        ->required(),
                    Select::make('status')
                        ->label(Labels::field('status'))
                        ->options(PickupRequestStatus::options())
                        ->default(PickupRequestStatus::Pending->value)
                        ->required(),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make(__('codflow.delivery.ameex_pickup_section'))
                ->schema([
                    TextInput::make('pickup_address')
                        ->label(__('codflow.fields.pickup_address'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('pickup_phone')
                        ->label(__('codflow.fields.pickup_phone'))
                        ->tel()
                        ->required()
                        ->maxLength(30),
                    Select::make('ameex_city_id')
                        ->label(__('codflow.fields.ameex_city_id'))
                        ->options(fn (Get $get): array => self::cityOptions($get('delivery_company_id')))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(__('codflow.delivery.ameex_city_pickup_help'))
                        ->afterStateHydrated(function (Select $component, mixed $state, Get $get): void {
                            $resolved = self::resolveCityState($get('delivery_company_id'), $state);

                            if (filled($resolved) && (string) $resolved !== (string) $state) {
                                $component->state($resolved);
                            }
                        })
                        ->dehydrateStateUsing(fn (mixed $state, Get $get): ?string => self::resolveCityState($get('delivery_company_id'), $state)),
                    Placeholder::make('ameex_cities_missing')
                        ->label('')
                        ->content(__('codflow.delivery.ameex_cities_sync_required'))
                        ->visible(fn (Get $get): bool => self::isAmeexCompany($get('delivery_company_id'))
                            && self::cityOptions($get('delivery_company_id')) === []),
                    Textarea::make('notes')
                        ->label(Labels::field('notes'))
                        ->helperText(__('codflow.delivery.ameex_pickup_note_help'))
                        ->columnSpanFull(),
                    TextInput::make('ameex_request_ref')
                        ->label(__('codflow.fields.ameex_request_ref'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                    TextInput::make('ameex_status')
                        ->label(__('codflow.fields.ameex_status'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                ])
                ->columns(2)
                ->visible(fn (Get $get): bool => self::isAmeexCompany($get('delivery_company_id')))
                ->columnSpanFull(),
        ]);
    }

    /** @return array<string, string> */
    public static function cityOptions(mixed $companyId): array
    {
        $map = self::citiesMap($companyId);

        if ($map === []) {
            return [];
        }

        $options = [];

        foreach ($map as $id => $name) {
            $options[(string) $id] = (string) $name;
        }

        asort($options);

        return $options;
    }

    /** @return array<string, string> */
    public static function citiesMap(mixed $companyId): array
    {
        if (blank($companyId)) {
            return [];
        }

        $company = DeliveryCompany::query()->find($companyId);

        if ($company === null) {
            return [];
        }

        return app(AmeexDeliveryService::class)->normalizedCitiesMap($company);
    }

    public static function resolveCityState(mixed $companyId, mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (blank($companyId)) {
            return trim((string) $state);
        }

        $company = DeliveryCompany::query()->find($companyId);

        if ($company === null) {
            return trim((string) $state);
        }

        $resolved = app(AmeexDeliveryService::class)->resolveCityId($company, trim((string) $state));

        return $resolved ?? trim((string) $state);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeAmeexPickupData(array $data): array
    {
        if (! self::isAmeexCompany($data['delivery_company_id'] ?? null)) {
            return $data;
        }

        $data['ameex_city_id'] = self::resolveCityState(
            $data['delivery_company_id'] ?? null,
            $data['ameex_city_id'] ?? null,
        );

        return $data;
    }

    protected static function isAmeexCompany(mixed $companyId): bool
    {
        if (blank($companyId)) {
            return false;
        }

        $company = DeliveryCompany::query()->find($companyId);

        return $company?->provider === DeliveryProvider::Ameex;
    }
}
