<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Services\TrackingService;

class ShipmentObserver
{
    public function updated(Shipment $shipment): void
    {
        if ($shipment->wasChanged('delivery_status')) {
            app(TrackingService::class)->syncOrderFromShipment($shipment);
        }
    }
}
