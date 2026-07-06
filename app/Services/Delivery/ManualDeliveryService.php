<?php

namespace App\Services\Delivery;

use App\Contracts\DeliveryCompanyServiceInterface;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\Shipment;

class ManualDeliveryService implements DeliveryCompanyServiceInterface
{
    public function isConfigured(DeliveryCompany $company): bool
    {
        return true;
    }

    public function createShipment(DeliveryCompany $company, Order $order, Shipment $shipment): array
    {
        return [
            'success' => false,
            'message' => __('codflow.delivery.manual_provider'),
        ];
    }

    public function refreshTracking(DeliveryCompany $company, Shipment $shipment): array
    {
        return [
            'success' => false,
            'message' => __('codflow.delivery.manual_provider'),
        ];
    }
}
