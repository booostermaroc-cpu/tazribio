<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryProvider;
use App\Models\DeliveryCompany;

class DeliveryServiceFactory
{
    public static function make(DeliveryCompany $company): \App\Contracts\DeliveryCompanyServiceInterface
    {
        $provider = $company->provider instanceof DeliveryProvider
            ? $company->provider
            : (DeliveryProvider::tryFrom((string) ($company->provider ?? '')) ?? DeliveryProvider::Manual);

        return match ($provider) {
            DeliveryProvider::Ameex => app(AmeexDeliveryService::class),
            default => app(ManualDeliveryService::class),
        };
    }
}
