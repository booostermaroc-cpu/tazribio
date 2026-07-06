<?php

namespace App\Notifications;

use App\Models\AgentCommission;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AgentCommissionCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public AgentCommission $commission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('codflow.notifications.commission_created_title'))
            ->body(__('codflow.notifications.commission_created_body', [
                'amount' => number_format((float) $this->commission->amount, 2),
                'order' => $this->commission->order?->order_number,
            ]))
            ->success()
            ->getDatabaseMessage();
    }
}
