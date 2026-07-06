<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('codflow.notifications.low_stock_title'),
            'body' => __('codflow.notifications.low_stock_body', [
                'product' => $this->product->name,
                'sku' => $this->product->sku,
                'stock' => $this->product->current_stock,
            ]),
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'danger',
            'status' => 'danger',
            'format' => 'filament',
            'duration' => 'persistent',
            'product_id' => $this->product->id,
        ];
    }
}
