<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Shipment;
use App\Notifications\AmeexTrackingUpdatedNotification;
use App\Services\Delivery\AmeexStatusMapper;

class AmeexWebhookService
{
    public function __construct(
        protected TrackingService $trackingService,
        protected DeliveryIntegrationService $deliveryIntegrationService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string}
     */
    public function handle(array $payload): array
    {
        $code = (string) ($payload['CODE'] ?? $payload['code'] ?? '');

        if (blank($code)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_invalid_response')];
        }

        $shipment = Shipment::query()
            ->where('tracking_number', $code)
            ->orWhere('ameex_parcel_code', $code)
            ->first();

        if (! $shipment) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_parcel_not_found')];
        }

        $fields = [
            'code' => $code,
            'statut' => $payload['STATUT'] ?? $payload['statut'] ?? null,
            'statut_name' => $payload['STATUT_NAME'] ?? $payload['statut_name'] ?? null,
            'statut_s' => $payload['STATUT_S'] ?? $payload['statut_s'] ?? null,
            'statut_s_name' => $payload['STATUT_S_NAME'] ?? $payload['statut_s_name'] ?? null,
            'comment' => $payload['COMMENT'] ?? $payload['comment'] ?? null,
            'date' => $payload['DATE'] ?? $payload['date'] ?? null,
        ];

        $this->deliveryIntegrationService->applyAmeexParcelFields($shipment, $fields, $payload);

        Activity::create([
            'user_id' => null,
            'action' => 'ameex_webhook',
            'description' => "Webhook Ameex pour le colis {$code} : ".($fields['statut_name'] ?? $fields['statut'] ?? 'mise à jour'),
        ]);

        if ($order = $shipment->fresh()->order) {
            $this->notificationService->notifyAdminsAndManagers(
                new AmeexTrackingUpdatedNotification($order, $shipment->fresh(), $fields['comment'] ?? null)
            );
        }

        return ['success' => true, 'message' => __('codflow.delivery.tracking_refreshed')];
    }
}
