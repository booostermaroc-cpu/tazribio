<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderTrackingHistory;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Support\OrderWorkflow;
use Illuminate\Support\Facades\Auth;

class TrackingService
{
    public function recordOrderStatusChange(Order $order, OrderStatus $status, ?string $description = null): OrderTrackingHistory
    {
        return OrderTrackingHistory::create([
            'order_id' => $order->id,
            'status' => $status->value,
            'description' => $description ?? __('codflow.tracking.status_updated', ['status' => $status->label()]),
            'changed_by' => Auth::id(),
        ]);
    }

    public function addTrackingEvent(
        Shipment $shipment,
        string $status,
        ?string $description = null,
        ?array $rawResponse = null,
    ): TrackingEvent {
        $event = TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $status,
            'description' => $description,
            'event_date' => now(),
            'raw_response' => $rawResponse,
        ]);

        $shipment->update(['last_tracking_update' => now()]);

        return $event;
    }

    public function syncShipmentStatus(Shipment $shipment, ShipmentStatus $status): Shipment
    {
        $shipment->update([
            'delivery_status' => $status,
            'last_tracking_update' => now(),
        ]);

        $this->addTrackingEvent(
            $shipment,
            $status->value,
            "Shipment status updated to {$status->label()}",
        );

        $this->syncOrderFromShipment($shipment->fresh());

        return $shipment->fresh();
    }

    public function syncOrderFromShipment(Shipment $shipment): void
    {
        $order = $shipment->order;

        if (! $order) {
            return;
        }

        $mappedOrderStatus = match ($shipment->delivery_status) {
            ShipmentStatus::Delivered => OrderStatus::Delivered,
            ShipmentStatus::Returned => OrderStatus::Returned,
            ShipmentStatus::InTransit, ShipmentStatus::PickedUp => OrderStatus::Shipped,
            default => null,
        };

        if ($mappedOrderStatus && $order->status !== $mappedOrderStatus) {
            app(OrderService::class)->transitionTowards($order, $mappedOrderStatus);
        }
    }
}
