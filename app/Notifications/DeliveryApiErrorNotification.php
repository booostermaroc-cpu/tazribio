<?php

namespace App\Notifications;

use App\Models\Order;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryApiErrorNotification extends Notification
{
    use Queueable;

    public function __construct(public ?Order $order, public string $errorMessage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('codflow.notifications.delivery_api_error_title'))
            ->body($this->errorMessage.($this->order ? ' ('.$this->order->order_number.')' : ''))
            ->danger()
            ->getDatabaseMessage();
    }
}
