<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentPayload(
            title: __('codflow.notifications.new_order_title'),
            body: __('codflow.notifications.new_order_body', [
                'order' => $this->order->order_number,
                'client' => $this->order->client?->full_name,
            ]),
            icon: 'heroicon-o-shopping-bag',
            status: 'info',
        );
    }

    /** @return array<string, mixed> */
    protected function filamentPayload(string $title, string $body, string $icon, string $status): array
    {
        return [
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'iconColor' => $status,
            'status' => $status,
            'format' => 'filament',
            'duration' => 'persistent',
            'order_id' => $this->order->id,
        ];
    }
}
