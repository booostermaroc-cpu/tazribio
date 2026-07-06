<?php

namespace App\Filament\Resources\Orders\Concerns;

use App\Enums\OrderConfirmationAction;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderValidationException;
use App\Models\Order;
use App\Services\ConfirmationTrackingService;
use App\Services\OrderService;
use App\Support\OrderWorkflow;
use App\Support\SmsUrl;
use App\Support\WhatsAppUrl;
use App\Filament\Support\OrderContactActions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

trait InteractsWithOrderConfirmationProcess
{
    /** @return list<Action> */
    public function getOrderConfirmationButtonActions(): array
    {
        return array_map(
            fn (Action $action): Action => $action->after(fn () => $this->refreshAfterConfirmationAction()),
            $this->buildOrderConfirmationButtonActions(),
        );
    }

    /** @return list<Action> */
    protected function buildOrderConfirmationButtonActions(): array
    {
        return [
            Action::make('confirmViaWhatsapp')
                ->label(__('codflow.order.status_confirmed_whatsapp'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::ConfirmedViaWhatsapp)),
            Action::make('confirmViaCall')
                ->label(__('codflow.order.status_confirmed_call'))
                ->icon(Heroicon::OutlinedPhoneArrowUpRight)
                ->color('primary')
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::ConfirmedViaCall)),
            Action::make('statusNoAnswer')
                ->label(__('codflow.order.status_no_answer'))
                ->icon(Heroicon::OutlinedPhoneXMark)
                ->color('warning')
                ->outlined()
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::RefusalNoAnswer)),
            Action::make('statusBusy')
                ->label(__('codflow.order.status_busy'))
                ->icon(Heroicon::OutlinedClock)
                ->color('warning')
                ->outlined()
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::ContactBusy)),
            Action::make('statusVoicemail')
                ->label(__('codflow.order.status_voicemail'))
                ->icon(Heroicon::OutlinedSpeakerWave)
                ->color('warning')
                ->outlined()
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::ContactVoicemail)),
            Action::make('statusWrongNumber')
                ->label(__('codflow.order.status_wrong_number'))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->outlined()
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::RefusalWrongNumber)),
            Action::make('statusCancelled')
                ->label(__('codflow.order.status_cancelled'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->applyConfirmationButton(OrderConfirmationAction::RefusalClientRefuses)),
            Action::make('logSmsContact')
                ->label(__('codflow.order.send_sms'))
                ->icon(Heroicon::OutlinedDevicePhoneMobile)
                ->color('gray')
                ->outlined()
                ->action(fn () => $this->applyConfirmationButton(
                    OrderConfirmationAction::SmsContact,
                    SmsUrl::url($this->getRecord()->client?->phone, OrderContactActions::orderMessage($this->getRecord())),
                )),
        ];
    }

    public function confirmationActionsVisible(): bool
    {
        return $this->record instanceof Order
            && OrderWorkflow::isConfirmationPhase($this->record->status);
    }

    /** @return list<Action> */
    public function getOrderFulfillmentButtonActions(): array
    {
        return array_map(
            fn (Action $action): Action => $action->after(fn () => $this->refreshAfterConfirmationAction()),
            $this->buildOrderFulfillmentButtonActions(),
        );
    }

    public function fulfillmentActionsVisible(): bool
    {
        if (! $this->record instanceof Order) {
            return false;
        }

        return in_array($this->record->status, [OrderStatus::Confirmed, OrderStatus::Prepared], true);
    }

    /** @return list<Action> */
    protected function buildOrderFulfillmentButtonActions(): array
    {
        return [
            Action::make('markPrepared')
                ->label(__('codflow.order.mark_prepared'))
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('info')
                ->visible(fn (): bool => OrderWorkflow::canTransition($this->getRecord()->status, OrderStatus::Prepared))
                ->action(fn () => $this->applyFulfillmentTransition(OrderStatus::Prepared)),
            Action::make('markShipped')
                ->label(__('codflow.order.mark_shipped'))
                ->icon(Heroicon::OutlinedTruck)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::Confirmed
                    || OrderWorkflow::canTransition($this->getRecord()->status, OrderStatus::Shipped))
                ->action(fn () => $this->applyFulfillmentTransition(OrderStatus::Shipped)),
        ];
    }

    public function openWhatsAppContactAction(): Action
    {
        $order = $this->getRecord();
        $phone = $order->client?->phone;
        $url = WhatsAppUrl::url($phone, OrderContactActions::orderMessage($order));

        return Action::make('contactWhatsApp')
            ->label(__('codflow.order.contact_whatsapp'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn (): bool => $url !== null)
            ->action(fn () => $this->logWhatsAppContactAndOpen($url));
    }

    protected function logWhatsAppContactAndOpen(?string $url): void
    {
        $order = $this->getRecord();

        app(ConfirmationTrackingService::class)->logWithStatusNote(
            $order,
            OrderConfirmationAction::WhatsappContact,
        );

        Notification::make()
            ->title(__('codflow.notifications.success'))
            ->body(__('codflow.order.step_logged', ['action' => OrderConfirmationAction::WhatsappContact->label()]))
            ->success()
            ->send();

        if ($url) {
            $this->redirect($url, navigate: false);
        }
    }

    protected function applyFulfillmentTransition(OrderStatus $status): void
    {
        $order = $this->getRecord();
        $trackingAction = match ($status) {
            OrderStatus::Prepared => OrderConfirmationAction::OrderPrepared,
            OrderStatus::Shipped => OrderConfirmationAction::OrderShipped,
            default => null,
        };

        try {
            if ($status === OrderStatus::Shipped) {
                if ($order->fresh()->status === OrderStatus::Confirmed) {
                    app(OrderService::class)->transitionTowards($order->fresh(), OrderStatus::Shipped);
                } else {
                    app(OrderService::class)->transitionTo($order->fresh(), OrderStatus::Shipped);
                }
            } else {
                app(OrderService::class)->transitionTo($order, $status);
            }

            if ($trackingAction !== null) {
                app(ConfirmationTrackingService::class)->logWithStatusNote(
                    $order->fresh(),
                    $trackingAction,
                    $status,
                );
            }

            Notification::make()
                ->title(__('codflow.notifications.success'))
                ->body(__('codflow.order.status_changed', ['status' => $status->label()]))
                ->success()
                ->send();
        } catch (InvalidOrderTransitionException|InsufficientStockException|OrderValidationException $exception) {
            Notification::make()
                ->title(__('codflow.notifications.error'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function applyConfirmationButton(OrderConfirmationAction $action, ?string $redirectUrl = null): void
    {
        $order = $this->getRecord();

        if ($redirectUrl !== null && blank($order->client?->phone)) {
            Notification::make()
                ->title(__('codflow.notifications.error'))
                ->body(__('codflow.order.no_client_phone'))
                ->danger()
                ->send();

            return;
        }

        $targetStatus = $action->targetOrderStatus();

        app(ConfirmationTrackingService::class)->logWithStatusNote(
            $order,
            $action,
            $targetStatus,
        );

        if ($targetStatus !== null && $order->status !== $targetStatus) {
            try {
                app(OrderService::class)->transitionTo($order, $targetStatus);
                $order = $this->getRecord()->fresh();
            } catch (InvalidOrderTransitionException|InsufficientStockException|OrderValidationException $exception) {
                Notification::make()
                    ->title(__('codflow.notifications.error'))
                    ->body($exception->getMessage())
                    ->danger()
                    ->send();

                return;
            }
        }

        $noteLine = __('codflow.order.refusal_note_prefix').' '.$action->label().' — '.now()->format('d/m/Y H:i');
        $order->update([
            'notes' => trim(($order->notes ? $order->notes."\n" : '').$noteLine),
        ]);

        Notification::make()
            ->title(__('codflow.notifications.success'))
            ->body(__('codflow.order.step_logged', ['action' => $action->label()]))
            ->success()
            ->send();

        if ($redirectUrl) {
            $this->redirect($redirectUrl, navigate: false);
        }
    }

    protected function refreshOrderRecord(): void
    {
        if (! $this->record instanceof Order) {
            return;
        }

        $this->record->refresh();

        if (method_exists($this, 'refreshFormData')) {
            $this->refreshFormData(['status', 'notes']);
        }

        if (method_exists($this, 'getSchema')) {
            $infolist = $this->getSchema('infolist');

            if ($infolist !== null) {
                $infolist->record($this->record);
            }
        }
    }

    protected function refreshAfterConfirmationAction(): void
    {
        $this->refreshOrderRecord();
    }

    abstract protected function transitionOrder(Order $record, OrderStatus $status): void;
}
