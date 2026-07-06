<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('codflow.notifications.order_returned_title'),
            'body' => __('codflow.notifications.order_returned_body', ['order' => $this->order->order_number]),
            'icon' => 'heroicon-o-arrow-uturn-left',
            'iconColor' => 'warning',
            'status' => 'warning',
            'format' => 'filament',
            'duration' => 'persistent',
            'order_id' => $this->order->id,
        ];
    }
}
