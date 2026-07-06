<?php

namespace App\Support;

class WhatsAppUrl
{
    public static function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '212')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '212'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && in_array($digits[0], ['5', '6', '7'], true)) {
            return '212'.$digits;
        }

        return $digits;
    }

    public static function url(?string $phone, ?string $message = null): ?string
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized === null) {
            return null;
        }

        $url = 'https://wa.me/'.$normalized;

        if (filled($message)) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    public static function telUrl(?string $phone): ?string
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized === null) {
            return null;
        }

        return 'tel:+'.$normalized;
    }
}
