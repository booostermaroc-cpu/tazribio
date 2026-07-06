<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ComplaintCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Complaint $complaint) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('codflow.notifications.complaint_created_title'),
            'body' => __('codflow.notifications.complaint_created_body', [
                'subject' => $this->complaint->subject,
                'order' => $this->complaint->order?->order_number,
            ]),
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'iconColor' => 'warning',
            'status' => 'warning',
            'format' => 'filament',
            'duration' => 'persistent',
            'complaint_id' => $this->complaint->id,
        ];
    }
}
