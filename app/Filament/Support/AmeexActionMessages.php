<?php

namespace App\Filament\Support;

use App\Models\DeliveryCompany;
use App\Services\Delivery\AmeexDeliveryService;

class AmeexActionMessages
{
    public static function stockSendConfirm(?DeliveryCompany $company = null): string
    {
        if ($company !== null && app(AmeexDeliveryService::class)->sendsWithoutStockCheck($company)) {
            $key = 'codflow.delivery.ameex_stock_confirm_text_only';
            $message = __($key);

            if ($message !== $key) {
                return $message;
            }

            return 'Le colis sera envoyé à Ameex avec la description produit uniquement, sans déduction du stock entrepôt Ameex.';
        }

        $key = 'codflow.delivery.ameex_stock_confirm';
        $message = __($key);

        if ($message === $key) {
            return 'Les produits seront envoyés à Ameex en mode STOCK avec leurs références SKU.';
        }

        return $message;
    }
}
