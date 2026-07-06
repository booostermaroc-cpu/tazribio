<?php

namespace App\Notifications;

use App\Models\Order;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentPendingNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('codflow.notifications.payment_pending_title'))
            ->body(__('codflow.notifications.payment_pending_body', ['order' => $this->order->order_number]))
            ->warning()
            ->getDatabaseMessage();
    }
}
