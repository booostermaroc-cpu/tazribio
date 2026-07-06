<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderValidationException;
use App\Filament\Resources\Orders\Concerns\InteractsWithOrderConfirmationProcess;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderCalculationService;
use App\Services\OrderPaymentValidator;
use App\Services\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use InteractsWithOrderConfirmationProcess;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->openWhatsAppContactAction(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $raw = $this->form->getRawState();
        $items = array_values(is_array($raw['items'] ?? []) ? $raw['items'] : []);

        if ($items === []) {
            throw OrderValidationException::noItems();
        }

        $payload = array_merge($data, $raw, ['items' => $items]);

        app(OrderPaymentValidator::class)->validate($payload);

        foreach ($items as $item) {
            if ((float) ($item['quantity'] ?? 0) <= 0) {
                throw OrderValidationException::invalidItemQuantity();
            }

            if ((float) ($item['unit_price'] ?? 0) < 0) {
                throw OrderValidationException::invalidItemPrice();
            }
        }

        return app(OrderCalculationService::class)->applyCalculatedAmounts($payload);
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (InvalidOrderTransitionException|InsufficientStockException|OrderValidationException $exception) {
            Notification::make()
                ->title(__('codflow.validation.form_error'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
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
}
