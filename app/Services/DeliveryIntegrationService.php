<?php

namespace App\Services;

use App\Enums\DeliveryProvider;
use App\Enums\OrderStatus;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Delivery\AmeexDeliveryService;
use App\Services\Delivery\AmeexStatusMapper;
use App\Services\Delivery\DeliveryServiceFactory;
use App\Enums\ShipmentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DeliveryIntegrationService
{
    public function __construct(
        protected TrackingService $trackingService,
        protected NotificationService $notificationService,
    ) {}

    /** @return array{success: bool, message: string} */
    public function sendOrderToCarrier(Order $order, ?DeliveryCompany $company = null): array
    {
        @set_time_limit(120);

        try {
            return $this->sendOrderToCarrierInternal($order, $company);
        } catch (\Throwable $exception) {
            Log::error('sendOrderToCarrier exception', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{success: bool, message: string} */
    protected function sendOrderToCarrierInternal(Order $order, ?DeliveryCompany $company = null): array
    {
        $company ??= $this->resolveCompany($order);

        if (! $company) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company')];
        }

        $order = $this->ensureDeliveryFields($order->loadMissing(['client', 'items.product']));
        $service = DeliveryServiceFactory::make($company);

        if ($service instanceof AmeexDeliveryService) {
            $validation = $service->validateCreateShipmentOrder($company, $order);

            if (! $validation['success']) {
                return [
                    'success' => false,
                    'message' => $validation['message'] ?? __('codflow.delivery.api_error'),
                ];
            }
        }

        $shipment = $order->shipments()->first() ?? $order->shipment;

        if ($shipment && $this->hasCarrierReference($shipment)) {
            return [
                'success' => false,
                'message' => __('codflow.delivery.already_sent_to_carrier'),
            ];
        }

        if (! $shipment) {
            $shipment = Shipment::query()->create([
                'order_id' => $order->id,
                'delivery_company_id' => $company->id,
                'tracking_number' => 'PENDING-'.$order->order_number,
                'delivery_status' => ShipmentStatus::Pending,
            ]);
        }

        $result = $service->createShipment($company, $order->loadMissing(['client', 'items.product']), $shipment);

        if ($result['success']) {
            if (blank($result['tracking_number'] ?? null)) {
                try {
                    $this->notificationService->deliveryApiError($order, __('codflow.delivery.ameex_invalid_response'));
                } catch (\Throwable $exception) {
                    Log::warning('deliveryApiError notification failed', [
                        'order_id' => $order->id,
                        'error' => $exception->getMessage(),
                    ]);
                }

                return ['success' => false, 'message' => __('codflow.delivery.ameex_invalid_response')];
            }

            $update = [
                'delivery_company_id' => $company->id,
                'tracking_number' => $result['tracking_number'],
                'ameex_parcel_code' => $result['tracking_number'],
            ];

            if (filled($result['delivery_note_ref'] ?? null)) {
                $update['ameex_delivery_note_ref'] = $result['delivery_note_ref'];
            }

            if (isset($result['raw'])) {
                $update['ameex_raw_response'] = $this->sanitizeRawResponse($result['raw']);
            }

            $shipment->update($update);

            try {
                $this->trackingService->addTrackingEvent(
                    $shipment,
                    ShipmentStatus::Pending->value,
                    __('codflow.delivery.sent_to_carrier'),
                    isset($result['raw']) ? $this->sanitizeRawResponse($result['raw']) : null,
                );
            } catch (\Throwable $exception) {
                Log::warning('addTrackingEvent failed after Ameex send', [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'error' => $exception->getMessage(),
                ]);
            }

            try {
                $this->notificationService->orderSentToDelivery($order, $company);
            } catch (\Throwable $exception) {
                Log::warning('orderSentToDelivery notification failed', [
                    'order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]);
            }

            return ['success' => true, 'message' => $result['message']];
        }

        try {
            $this->notificationService->deliveryApiError($order, $result['message']);
        } catch (\Throwable $exception) {
            Log::warning('deliveryApiError notification failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (isset($result['raw'])) {
            $shipment->update(['ameex_raw_response' => $this->sanitizeRawResponse($result['raw'])]);
        }

        return ['success' => false, 'message' => $result['message']];
    }

    /** @param  mixed  $raw */
    protected function sanitizeRawResponse(mixed $raw): mixed
    {
        if (! is_array($raw)) {
            return is_string($raw) ? mb_substr($raw, 0, 5000) : $raw;
        }

        $encoded = json_encode($raw, JSON_UNESCAPED_UNICODE);

        if ($encoded !== false && strlen($encoded) <= 60000) {
            return $raw;
        }

        return [
            'truncated' => true,
            'preview' => mb_substr($encoded ?: '', 0, 5000),
        ];
    }

    protected function hasCarrierReference(Shipment $shipment): bool
    {
        return filled($shipment->ameex_parcel_code)
            || (filled($shipment->tracking_number) && ! str_starts_with((string) $shipment->tracking_number, 'PENDING-'));
    }

    /** @return array{success: bool, message: string} */
    public function refreshShipmentTracking(Shipment $shipment): array
    {
        $company = $shipment->deliveryCompany;

        if (! $company) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company')];
        }

        $service = DeliveryServiceFactory::make($company);
        $result = $service->refreshTracking($company, $shipment);

        if ($result['success'] && isset($result['parcel'])) {
            $this->applyAmeexParcelFields($shipment, $result['parcel'], $result['raw'] ?? null);

            return ['success' => true, 'message' => $result['message']];
        }

        if ($shipment->order) {
            $this->notificationService->deliveryApiError($shipment->order, $result['message']);
        }

        return ['success' => false, 'message' => $result['message']];
    }

    /** @return array{success: bool, message: string} */
    public function fetchShipmentInfo(Shipment $shipment): array
    {
        $company = $this->requireAmeexCompany($shipment);

        if (! $company['success']) {
            return $company;
        }

        $ameex = app(AmeexDeliveryService::class);
        $result = $ameex->getMassInfo($company['company'], [$shipment->tracking_number]);

        if (! $result['success']) {
            return $this->failShipment($shipment, $result['message']);
        }

        $parcel = collect($result['parcels'] ?? [])->first();

        if (! $parcel) {
            return $this->failShipment($shipment, __('codflow.delivery.ameex_parcel_not_found'));
        }

        $this->applyAmeexParcelFields($shipment, $parcel, $result['raw'] ?? null);

        return ['success' => true, 'message' => __('codflow.delivery.ameex_info_success')];
    }

    /** @param  Collection<int, Shipment>  $shipments */
    public function refreshMassTracking(Collection $shipments): array
    {
        return $this->runMassAction($shipments, 'massTracking', __('codflow.delivery.tracking_refreshed'));
    }

    /** @param  Collection<int, Shipment>  $shipments */
    public function fetchMassInfo(Collection $shipments): array
    {
        return $this->runMassAction($shipments, 'getMassInfo', __('codflow.delivery.ameex_info_success'));
    }

    /** @return array{success: bool, message: string} */
    public function relaunchShipment(Shipment $shipment): array
    {
        $company = $this->requireAmeexCompany($shipment);

        if (! $company['success']) {
            return $company;
        }

        $parcelCode = $shipment->ameex_parcel_code ?: $shipment->tracking_number;
        $result = app(AmeexDeliveryService::class)->relaunchParcel($company['company'], (string) $parcelCode);

        if ($result['success']) {
            $shipment->update(['ameex_raw_response' => $result['raw'] ?? $shipment->ameex_raw_response]);
            $this->trackingService->addTrackingEvent(
                $shipment,
                'relaunch',
                __('codflow.delivery.ameex_relaunch_success'),
                $result['raw'] ?? null,
            );

            return ['success' => true, 'message' => $result['message']];
        }

        return $this->failShipment($shipment, $result['message']);
    }

    /** @param  array<string, mixed>  $customerData */
    public function relaunchShipmentWithCustomer(Shipment $shipment, array $customerData): array
    {
        $company = $this->requireAmeexCompany($shipment);

        if (! $company['success']) {
            return $company;
        }

        $parcelCode = $shipment->ameex_parcel_code ?: $shipment->tracking_number;
        $result = app(AmeexDeliveryService::class)->relaunchParcelNewCustomer(
            $company['company'],
            (string) $parcelCode,
            $customerData,
        );

        if ($result['success']) {
            $shipment->update(['ameex_raw_response' => $result['raw'] ?? $shipment->ameex_raw_response]);

            return ['success' => true, 'message' => $result['message']];
        }

        return $this->failShipment($shipment, $result['message']);
    }

    /** @param  array<string, mixed>  $fields */
    public function applyAmeexParcelFields(Shipment $shipment, array $fields, mixed $raw = null): void
    {
        $mapped = AmeexStatusMapper::mapToShipmentStatus(
            $fields['statut'] ?? null,
            $fields['statut_name'] ?? null,
            $fields['statut_s'] ?? null,
            $fields['statut_s_name'] ?? null,
        );

        $update = array_filter([
            'ameex_parcel_code' => $fields['parcel_code'] ?? $fields['code'] ?? null,
            'ameex_last_status' => $fields['statut'] ?? null,
            'ameex_last_status_name' => $fields['statut_name'] ?? null,
            'ameex_last_sub_status' => $fields['statut_s'] ?? $fields['statut_s_name'] ?? null,
            'ameex_delivery_note_ref' => $fields['delivery_note_ref'] ?? null,
            'ameex_raw_response' => is_array($raw) ? $raw : $shipment->ameex_raw_response,
            'last_tracking_update' => now(),
        ], fn ($v) => $v !== null);

        if ($mapped) {
            $update['delivery_status'] = $mapped;
        }

        $shipment->update($update);

        $this->trackingService->addTrackingEvent(
            $shipment,
            (string) ($fields['statut'] ?? $mapped?->value ?? 'updated'),
            $fields['comment'] ?? __('codflow.delivery.tracking_refreshed'),
            is_array($raw) ? $raw : null,
        );

        if ($mapped && ($order = $shipment->order)) {
            $orderStatus = AmeexStatusMapper::mapToOrderStatus($mapped);

            if ($orderStatus && $order->status !== $orderStatus) {
                app(OrderService::class)->transitionTowards($order, $orderStatus);
            }
        }
    }

    protected function hasActiveCarrierTracking(Order $order): bool
    {
        return $order->shipments()
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNotNull('tracking_number')
                        ->where('tracking_number', 'not like', 'PENDING-%');
                })->orWhereNotNull('ameex_parcel_code');
            })
            ->exists();
    }

    /** @return array{success: bool, message: string, url?: string} */
    public function resolveAmeexDeliveryNoteUrl(Shipment $shipment, bool $download = false): array
    {
        if (blank($shipment->ameex_delivery_note_ref)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_ref_missing')];
        }

        if (! $shipment->deliveryCompany) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company')];
        }

        return [
            'success' => true,
            'message' => __('codflow.delivery.ameex_print_success'),
            'url' => route('ameex.delivery-note', ['shipment' => $shipment, 'download' => $download ? 1 : 0]),
        ];
    }

    /** @return array{success: bool, message: string, raw?: array<string, mixed>|null} */
    public function sendShipmentOrderToAmeex(Shipment $shipment): array
    {
        $company = $shipment->deliveryCompany;

        if (! $company) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company')];
        }

        if ($company->provider !== DeliveryProvider::Ameex) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_provider_required')];
        }

        return app(AmeexDeliveryService::class)->createAmeexOrder(
            $company,
            $shipment->fresh(['order.client', 'order.items.product']),
        );
    }

    /** @return array{success: bool, message: string, company?: DeliveryCompany} */
    protected function requireAmeexCompany(Shipment $shipment): array
    {
        $company = $shipment->deliveryCompany;

        if (! $company) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company')];
        }

        if ($company->provider !== DeliveryProvider::Ameex) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_provider_required')];
        }

        if (! app(AmeexDeliveryService::class)->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        if (blank($shipment->tracking_number)) {
            return ['success' => false, 'message' => __('codflow.delivery.no_tracking_number')];
        }

        return ['success' => true, 'message' => 'OK', 'company' => $company];
    }

    /** @param  Collection<int, Shipment>  $shipments */
    protected function runMassAction(Collection $shipments, string $method, string $successMessage): array
    {
        if ($shipments->isEmpty()) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_no_codes')];
        }

        $company = $shipments->first()?->deliveryCompany;

        if (! $company || $company->provider !== DeliveryProvider::Ameex) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_provider_required')];
        }

        $ameex = app(AmeexDeliveryService::class);
        $codes = $shipments->pluck('tracking_number')->filter()->values()->all();
        $result = $ameex->{$method}($company, $codes);

        if (! $result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        foreach ($shipments as $shipment) {
            $parcel = collect($result['parcels'] ?? [])
                ->first(fn (array $p) => strcasecmp((string) ($p['code'] ?? ''), (string) $shipment->tracking_number) === 0);

            if ($parcel) {
                $this->applyAmeexParcelFields($shipment, $parcel, $result['raw'] ?? null);
            }
        }

        return ['success' => true, 'message' => $successMessage];
    }

    /** @return array{success: false, message: string} */
    protected function failShipment(Shipment $shipment, string $message): array
    {
        if ($shipment->order) {
            $this->notificationService->deliveryApiError($shipment->order, $message);
        }

        return ['success' => false, 'message' => $message];
    }

    protected function resolveCompany(Order $order): ?DeliveryCompany
    {
        $shipment = $order->shipments()->first() ?? $order->shipment;

        if ($shipment?->delivery_company_id) {
            return DeliveryCompany::query()->find($shipment->delivery_company_id);
        }

        $defaultId = SettingService::get()->default_delivery_company_id;

        return $defaultId
            ? DeliveryCompany::query()->find($defaultId)
            : DeliveryCompany::query()->where('is_active', true)->first();
    }

    protected function ensureDeliveryFields(Order $order): Order
    {
        $client = $order->client;
        $updates = [];

        if (blank($order->address) && filled($client?->address)) {
            $updates['address'] = $client->address;
        }

        if (blank($order->city) && filled($client?->city)) {
            $updates['city'] = $client->city;
        }

        if ($updates === []) {
            return $order;
        }

        $order->update($updates);

        return $order->fresh(['client', 'items.product']);
    }
}
