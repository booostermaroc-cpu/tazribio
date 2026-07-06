<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('codflow.notifications.payment_received_title'),
            'body' => __('codflow.notifications.payment_received_body', [
                'invoice' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
            ]),
            'icon' => 'heroicon-o-banknotes',
            'iconColor' => 'success',
            'status' => 'success',
            'format' => 'filament',
            'duration' => 'persistent',
            'invoice_id' => $this->invoice->id,
        ];
    }
}
