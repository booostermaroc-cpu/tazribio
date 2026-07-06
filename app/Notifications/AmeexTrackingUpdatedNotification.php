<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Shipment;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AmeexTrackingUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public Shipment $shipment,
        public ?string $comment = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('codflow.notifications.ameex_tracking_title'))
            ->body(__('codflow.notifications.ameex_tracking_body', [
                'order' => $this->order->order_number,
                'code' => $this->shipment->tracking_number,
                'status' => $this->shipment->ameex_last_status_name ?? $this->shipment->ameex_last_status ?? '—',
            ]))
            ->icon('heroicon-o-truck')
            ->getDatabaseMessage();
    }
}
