<?php

namespace App\Notifications;

use App\Models\DeliveryCompany;
use App\Models\Order;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderSentToDeliveryNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public DeliveryCompany $company) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('codflow.notifications.sent_to_delivery_title'))
            ->body(__('codflow.notifications.sent_to_delivery_body', [
                'order' => $this->order->order_number,
                'carrier' => $this->company->name,
            ]))
            ->success()
            ->getDatabaseMessage();
    }
}
