<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\DeliveryProvider;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Support\AmeexActionMessages;
use App\Filament\Support\AmeexNotifications;
use App\Models\Shipment;
use App\Services\DeliveryIntegrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendOrderToAmeex')
                ->label(__('codflow.delivery.send_order_ameex'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('codflow.delivery.send_order_ameex'))
                ->modalDescription(AmeexActionMessages::stockSendConfirm())
                ->modalSubmitActionLabel('Confirmer')
                ->visible(fn (Shipment $record) => $record->deliveryCompany?->provider === DeliveryProvider::Ameex
                    && $record->order !== null)
                ->action(function (Shipment $record): void {
                    AmeexNotifications::notify(app(DeliveryIntegrationService::class)->sendShipmentOrderToAmeex($record));
                    $this->record->refresh();
                }),
            Action::make('refreshAmeexTracking')
                ->label(__('codflow.delivery.refresh_tracking'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (Shipment $record) => $record->deliveryCompany?->provider === DeliveryProvider::Ameex)
                ->action(function (Shipment $record): void {
                    AmeexNotifications::notify(app(DeliveryIntegrationService::class)->refreshShipmentTracking($record));
                    $this->record->refresh();
                }),
            Action::make('ameexMassInfo')
                ->label(__('codflow.delivery.ameex_get_info'))
                ->icon(Heroicon::OutlinedInformationCircle)
                ->visible(fn (Shipment $record) => $record->deliveryCompany?->provider === DeliveryProvider::Ameex)
                ->action(function (Shipment $record): void {
                    AmeexNotifications::notify(app(DeliveryIntegrationService::class)->fetchShipmentInfo($record));
                    $this->record->refresh();
                }),
            Action::make('printAmeexDeliveryNote')
                ->label(__('codflow.delivery.ameex_print_bl'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('warning')
                ->visible(fn (Shipment $record) => $record->deliveryCompany?->provider === DeliveryProvider::Ameex)
                ->action(function (Shipment $record): void {
                    $result = app(DeliveryIntegrationService::class)->resolveAmeexDeliveryNoteUrl($record);

                    if (! $result['success']) {
                        AmeexNotifications::notify($result);

                        return;
                    }

                    $this->redirect($result['url'], navigate: false);
                }),
            Action::make('downloadAmeexDeliveryNote')
                ->label(__('codflow.delivery.ameex_download_bl'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (Shipment $record) => $record->deliveryCompany?->provider === DeliveryProvider::Ameex)
                ->action(function (Shipment $record): void {
                    $result = app(DeliveryIntegrationService::class)->resolveAmeexDeliveryNoteUrl($record, true);

                    if (! $result['success']) {
                        AmeexNotifications::notify($result);

                        return;
                    }

                    $this->redirect($result['url'], navigate: false);
                }),
            Action::make('relaunchAmeexParcel')
                ->label(__('codflow.delivery.ameex_relaunch'))
                ->icon(Heroicon::OutlinedArrowUturnRight)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Shipment $record) => $record->deliveryCompany?->provider === DeliveryProvider::Ameex)
                ->action(function (Shipment $record): void {
                    AmeexNotifications::notify(app(DeliveryIntegrationService::class)->relaunchShipment($record));
                    $this->record->refresh();
                }),
            DeleteAction::make(),
        ];
    }
}
