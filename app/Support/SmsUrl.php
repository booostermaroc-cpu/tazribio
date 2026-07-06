<?php

namespace App\Support;

class SmsUrl
{
    public static function url(?string $phone, ?string $message = null): ?string
    {
        $normalized = WhatsAppUrl::normalizePhone($phone);

        if ($normalized === null) {
            return null;
        }

        $url = 'sms:+'.$normalized;

        if (filled($message)) {
            $url .= '?body='.rawurlencode($message);
        }

        return $url;
    }
}
