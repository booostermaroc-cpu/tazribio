<?php

namespace App\Contracts;

use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\Shipment;

interface DeliveryCompanyServiceInterface
{
    public function isConfigured(DeliveryCompany $company): bool;

  /**
   * @return array{success: bool, tracking_number?: string, message: string, raw?: array<string, mixed>}
   */
    public function createShipment(DeliveryCompany $company, Order $order, Shipment $shipment): array;

  /**
   * @return array{success: bool, status?: string, message: string, raw?: array<string, mixed>}
   */
    public function refreshTracking(DeliveryCompany $company, Shipment $shipment): array;
}
