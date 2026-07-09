<?php

namespace App\Filament\Resources\DeliveryCompanies\Pages;

use App\Enums\DeliveryProvider;
use App\Filament\Resources\DeliveryCompanies\Concerns\InteractsWithAmeexBusinessIdForm;
use App\Filament\Resources\DeliveryCompanies\DeliveryCompanyResource;
use App\Filament\Support\AmeexNotifications;
use App\Models\DeliveryCompany;
use App\Services\AmeexImportService;
use App\Services\Delivery\AmeexDeliveryService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditDeliveryCompany extends EditRecord
{
    use InteractsWithAmeexBusinessIdForm;

    protected static string $resource = DeliveryCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testAmeexConnection')
                ->label(__('codflow.delivery.ameex_test_connection'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('info')
                ->visible(fn (DeliveryCompany $record) => $record->provider === DeliveryProvider::Ameex)
                ->action(function (DeliveryCompany $record): void {
                    AmeexNotifications::notify(app(AmeexDeliveryService::class)->testConnection($record));
                }),
            ActionGroup::make([
                Action::make('syncAmeexBusinesses')
                    ->label(__('codflow.delivery.ameex_sync_businesses'))
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->action(function (DeliveryCompany $record): void {
                        AmeexNotifications::notify(app(AmeexDeliveryService::class)->syncBusinesses($record));
                        $this->record->refresh();
                        $this->fillForm();
                    }),
                Action::make('syncAmeexCities')
                    ->label(__('codflow.delivery.ameex_sync_cities'))
                    ->icon(Heroicon::OutlinedMapPin)
                    ->action(function (DeliveryCompany $record): void {
                        AmeexNotifications::notify(app(AmeexDeliveryService::class)->syncCities($record));
                        $this->record->refresh();
                    }),
                Action::make('syncAmeexStatuses')
                    ->label(__('codflow.delivery.ameex_sync_statuses'))
                    ->icon(Heroicon::OutlinedListBullet)
                    ->action(function (DeliveryCompany $record): void {
                        AmeexNotifications::notify(app(AmeexDeliveryService::class)->getParcelStatuses($record));
                        $this->record->refresh();
                    }),
                Action::make('syncAmeexParcels')
                    ->label(__('codflow.delivery.ameex_sync_parcels'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (DeliveryCompany $record): void {
                        AmeexNotifications::notify(app(AmeexImportService::class)->syncCompanyShipments($record));
                    }),
            ])
                ->label(__('codflow.delivery.ameex_sync_group'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->button()
                ->color('gray')
                ->visible(fn (DeliveryCompany $record) => $record->provider === DeliveryProvider::Ameex),
            Action::make('testAmeexProducts')
                ->label(__('codflow.delivery.ameex_test_products'))
                ->icon(Heroicon::OutlinedCube)
                ->color('gray')
                ->visible(fn (DeliveryCompany $record) => $record->provider === DeliveryProvider::Ameex)
                ->action(function (DeliveryCompany $record): void {
                    AmeexNotifications::notify(app(AmeexDeliveryService::class)->testProductsEndpoint($record));
                    $this->record->refresh();
                }),
            DeleteAction::make(),
        ];
    }
}
