<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ReturnBon;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReturnScannedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public ReturnBon $returnBon) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('codflow.notifications.return_scanned_title'))
            ->body(__('codflow.notifications.return_scanned_body', [
                'order' => $this->order->order_number,
                'return' => $this->returnBon->return_number,
            ]))
            ->warning()
            ->getDatabaseMessage();
    }
}
