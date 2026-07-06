<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderConfirmationAction;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderValidationException;
use App\Filament\Resources\Orders\Concerns\InteractsWithOrderConfirmationProcess;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\AmeexNotifications;
use App\Models\Order;
use App\Services\ConfirmationTrackingService;
use App\Services\DeliveryIntegrationService;
use App\Services\OrderReviewService;
use App\Services\OrderService;
use App\Support\OrderWorkflow;
use App\Support\WhatsAppUrl;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    use InteractsWithOrderConfirmationProcess;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->openWhatsAppContactAction(),
            Action::make('sendReviewLink')
                ->label(__('codflow.review.send_link'))
                ->icon(Heroicon::OutlinedStar)
                ->color('warning')
                ->visible(fn (): bool => $this->record !== null && $this->canSendReviewLink($this->record))
                ->action(fn (Order $record) => $this->sendReviewLink($record)),
            ActionGroup::make($this->getWorkflowActions())
                ->label(__('codflow.order.quick_actions'))
                ->icon(Heroicon::OutlinedBolt)
                ->button(),
            ActionGroup::make([
                Action::make('sendToCarrier')
                    ->label(__('codflow.delivery.send_action'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => ! $record->shipments()
                        ->where(function ($query): void {
                            $query->where(function ($inner): void {
                                $inner->whereNotNull('tracking_number')
                                    ->where('tracking_number', 'not like', 'PENDING-%');
                            })->orWhereNotNull('ameex_parcel_code');
                        })
                        ->exists())
                    ->action(fn (Order $record) => $this->sendToCarrier($record)),
                Action::make('refreshTracking')
                    ->label(__('codflow.delivery.refresh_tracking'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->action(fn (Order $record) => $this->refreshTracking($record)),
                Action::make('getAmeexParcelInfo')
                    ->label(__('codflow.delivery.ameex_get_info'))
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->action(fn (Order $record) => $this->getParcelInfo($record)),
                Action::make('relaunchAmeexParcel')
                    ->label(__('codflow.delivery.ameex_relaunch'))
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->action(fn (Order $record) => $this->relaunchParcel($record)),
                Action::make('relaunchAmeexNewCustomer')
                    ->label(__('codflow.delivery.ameex_relaunch_new'))
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->form(fn (Order $record) => [
                        TextInput::make('order_num')->default($record->order_number)->required(),
                        TextInput::make('receiver')->default($record->client?->full_name)->required(),
                        TextInput::make('phone')->default($record->client?->phone)->required(),
                        TextInput::make('city')->default($record->city)->required(),
                        TextInput::make('address')->default($record->address)->required(),
                        Textarea::make('comment')->default($record->notes),
                        TextInput::make('price')->numeric()->default($record->final_amount)->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $shipment = $record->shipments()->first() ?? $record->shipment;

                        if (! $shipment) {
                            AmeexNotifications::notify(['success' => false, 'message' => __('codflow.delivery.no_shipment')]);

                            return;
                        }

                        AmeexNotifications::notify(
                            app(DeliveryIntegrationService::class)->relaunchShipmentWithCustomer($shipment, $data)
                        );
                        $this->record->refresh();
                    }),
                Action::make('printAmeexDeliveryNote')
                    ->label(__('codflow.delivery.ameex_print_bl'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->action(fn (Order $record) => $this->openAmeexBl($record)),
                Action::make('downloadAmeexDeliveryNote')
                    ->label(__('codflow.delivery.ameex_download_bl'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Order $record) => $record->shipments()->exists())
                    ->action(fn (Order $record) => $this->openAmeexBl($record, true)),
            ])
                ->label(__('codflow.ui.ameex'))
                ->icon(Heroicon::OutlinedTruck)
                ->color('info')
                ->button(),
            Action::make('downloadDeliveryNote')
                ->label(__('codflow.order.delivery_note'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->url(fn (Order $record) => route('documents.delivery-note', $record))
                ->openUrlInNewTab(),
            Action::make('printDeliveryNote')
                ->label(__('codflow.order.print'))
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn (Order $record) => route('documents.delivery-note', ['order' => $record, 'print' => 1]))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }

    protected function canSendReviewLink(Order $order): bool
    {
        if (blank($order->client?->phone)) {
            return false;
        }

        if ($order->review?->isSubmitted()) {
            return false;
        }

        return $order->status === OrderStatus::Delivered;
    }

    protected function sendReviewLink(Order $record): void
    {
        if (blank($record->client?->phone)) {
            Notification::make()
                ->title(__('codflow.notifications.error'))
                ->body(__('codflow.order.no_client_phone'))
                ->danger()
                ->send();

            return;
        }

        $reviewService = app(OrderReviewService::class);
        $review = $reviewService->markLinkSent($record);

        app(ConfirmationTrackingService::class)->logWithStatusNote(
            $record,
            OrderConfirmationAction::ReviewLinkSent,
        );

        $url = $reviewService->publicUrl($review);
        $waUrl = WhatsAppUrl::url($record->client->phone, $reviewService->whatsAppMessage($record, $url));

        Notification::make()
            ->title(__('codflow.notifications.success'))
            ->body(__('codflow.review.link_sent'))
            ->success()
            ->send();

        if ($waUrl) {
            $this->redirect($waUrl, navigate: false);
        }

        $this->record->refresh();
    }

    protected function sendToCarrier(Order $record): void
    {
        AmeexNotifications::notify(app(DeliveryIntegrationService::class)->sendOrderToCarrier($record));
        $this->record->refresh();
    }

    protected function refreshTracking(Order $record): void
    {
        $shipment = $record->shipments()->first() ?? $record->shipment;

        if (! $shipment) {
            AmeexNotifications::notify(['success' => false, 'message' => __('codflow.delivery.no_shipment')]);

            return;
        }

        AmeexNotifications::notify(app(DeliveryIntegrationService::class)->refreshShipmentTracking($shipment));
        $this->record->refresh();
    }

    protected function getParcelInfo(Order $record): void
    {
        $shipment = $record->shipments()->first() ?? $record->shipment;

        if (! $shipment) {
            AmeexNotifications::notify(['success' => false, 'message' => __('codflow.delivery.no_shipment')]);

            return;
        }

        AmeexNotifications::notify(app(DeliveryIntegrationService::class)->fetchShipmentInfo($shipment));
        $this->record->refresh();
    }

    protected function relaunchParcel(Order $record): void
    {
        $shipment = $record->shipments()->first() ?? $record->shipment;

        if (! $shipment) {
            AmeexNotifications::notify(['success' => false, 'message' => __('codflow.delivery.no_shipment')]);

            return;
        }

        AmeexNotifications::notify(app(DeliveryIntegrationService::class)->relaunchShipment($shipment));
        $this->record->refresh();
    }

    protected function openAmeexBl(Order $record, bool $download = false): void
    {
        $shipment = $record->shipments()->first() ?? $record->shipment;

        if (! $shipment) {
            AmeexNotifications::notify(['success' => false, 'message' => __('codflow.delivery.no_shipment')]);

            return;
        }

        $result = app(DeliveryIntegrationService::class)->resolveAmeexDeliveryNoteUrl($shipment, $download);

        if (! $result['success']) {
            AmeexNotifications::notify($result);

            return;
        }

        $this->redirect($result['url'], navigate: false);
    }

    /** @return array<Action> */
    protected function getWorkflowActions(): array
    {
        $actions = [];

        foreach ($this->workflowActionMap() as $statusValue => $config) {
            $status = OrderStatus::from($statusValue);

            $actions[] = Action::make($status->value)
                ->label($status->label())
                ->icon($config['icon'])
                ->color($config['color'])
                ->visible(fn (Order $record) => OrderWorkflow::canTransition($record->status, $status))
                ->requiresConfirmation($config['confirm'] ?? false)
                ->action(fn (Order $record) => $this->transitionOrder($record, $status));
        }

        return $actions;
    }

    protected function transitionOrder(Order $record, OrderStatus $status): void
    {
        try {
            app(OrderService::class)->transitionTo($record, $status);

            Notification::make()
                ->title(__('codflow.notifications.success'))
                ->body(__('codflow.order.status_changed', ['status' => $status->label()]))
                ->success()
                ->send();

            $this->refreshOrderRecord();
        } catch (InvalidOrderTransitionException|InsufficientStockException|OrderValidationException $exception) {
            Notification::make()
                ->title(__('codflow.notifications.error'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /** @return array<string, array{icon: string, color: string, confirm?: bool}> */
    protected function workflowActionMap(): array
    {
        return [
            OrderStatus::Prepared->value => ['icon' => Heroicon::OutlinedArchiveBox, 'color' => 'info'],
            OrderStatus::Shipped->value => ['icon' => Heroicon::OutlinedTruck, 'color' => 'warning', 'confirm' => true],
            OrderStatus::Delivered->value => ['icon' => Heroicon::OutlinedHome, 'color' => 'success', 'confirm' => true],
            OrderStatus::Returned->value => ['icon' => Heroicon::OutlinedArrowUturnLeft, 'color' => 'danger', 'confirm' => true],
            OrderStatus::Cancelled->value => ['icon' => Heroicon::OutlinedXCircle, 'color' => 'danger', 'confirm' => true],
        ];
    }
}
