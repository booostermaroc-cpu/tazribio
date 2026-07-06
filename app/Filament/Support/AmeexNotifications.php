<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;

class AmeexNotifications
{
    /** @param  array{success: bool, message: string}  $result */
    public static function notify(array $result): void
    {
        Notification::make()
            ->title($result['success'] ? __('codflow.notifications.success') : __('codflow.notifications.error'))
            ->body($result['message'])
            ->{$result['success'] ? 'success' : 'danger'}()
            ->send();
    }
}
