<?php

namespace App\Filament\Resources\PickupRequests\Pages;

use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Filament\Resources\PickupRequests\Schemas\PickupRequestForm;
use App\Filament\Support\AmeexNotifications;
use App\Services\PickupIntegrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPickupRequest extends EditRecord
{
    protected static string $resource = PickupRequestResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return PickupRequestForm::normalizeAmeexPickupData($data);
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PickupRequestForm::normalizeAmeexPickupData($data);
    }

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
                    $state = PickupRequestForm::normalizeAmeexPickupData($this->form->getRawState());

                    $this->record->update([
                        'pickup_address' => $state['pickup_address'] ?? $this->record->pickup_address,
                        'pickup_phone' => $state['pickup_phone'] ?? $this->record->pickup_phone,
                        'ameex_city_id' => $state['ameex_city_id'] ?? $this->record->ameex_city_id,
                        'notes' => $state['notes'] ?? $this->record->notes,
                    ]);

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
