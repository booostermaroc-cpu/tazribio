<?php

namespace App\Filament\Support;

class AmeexActionMessages
{
    public static function stockSendConfirm(): string
    {
        $key = 'codflow.delivery.ameex_stock_confirm';
        $message = __($key);

        if ($message === $key) {
            return 'Les produits seront envoyés à Ameex en mode STOCK avec leurs références SKU.';
        }

        return $message;
    }
}
