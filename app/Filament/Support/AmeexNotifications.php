<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;

class AmeexNotifications
{
    /** @param  array{success: bool, message: string}  $result */
    public static function notify(array $result): void
    {
        $message = (string) ($result['message'] ?? '');

        if (str_starts_with($message, 'codflow.')) {
            $key = str_replace('codflow.delivery.', '', $message);
            $resolved = AmeexLabels::delivery($key);

            if ($resolved !== $key) {
                $message = $resolved;
            }
        }

        Notification::make()
            ->title($result['success'] ? __('codflow.notifications.success') : __('codflow.notifications.error'))
            ->body($message)
            ->{$result['success'] ? 'success' : 'danger'}()
            ->send();
    }
}
