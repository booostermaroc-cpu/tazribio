<?php

namespace App\Services;

use App\Enums\DeliveryProvider;
use App\Models\PickupRequest;
use App\Services\Delivery\AmeexDeliveryService;

class PickupIntegrationService
{
    /** @return array{success: bool, message: string} */
    public function sendToAmeex(PickupRequest $pickup): array
    {
        $company = $pickup->deliveryCompany;

        if (! $company) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company')];
        }

        if ($company->provider !== DeliveryProvider::Ameex) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_provider_required')];
        }

        $result = app(AmeexDeliveryService::class)->createPickupRequest($company, $pickup);

        if (! $result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        return ['success' => true, 'message' => $result['message']];
    }
}
