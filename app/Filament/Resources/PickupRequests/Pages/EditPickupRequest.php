<?php

namespace App\Filament\Resources\PickupRequests\Pages;

use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Filament\Support\AmeexNotifications;
use App\Services\PickupIntegrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPickupRequest extends EditRecord
{
    protected static string $resource = PickupRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendToAmeex')
                ->label(__('codflow.delivery.ameex_send_pickup'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->deliveryCompany?->provider?->value === 'ameex')
                ->action(function (): void {
                    AmeexNotifications::notify(
                        app(PickupIntegrationService::class)->sendToAmeex($this->record->fresh())
                    );
                    $this->record->refresh();
                }),
            Action::make('refreshAmeexPickupStatus')
                ->label(__('codflow.delivery.ameex_refresh_pickup'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn () => filled($this->record->ameex_request_ref))
                ->action(function (): void {
                    AmeexNotifications::notify([
                        'success' => false,
                        'message' => __('codflow.delivery.ameex_pickup_status_not_available'),
                    ]);
                }),
            DeleteAction::make(),
        ];
    }
}
