<?php

namespace App\Services\Delivery;

use App\Contracts\DeliveryCompanyServiceInterface;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmeexDeliveryService implements DeliveryCompanyServiceInterface
{
    public const DEFAULT_BASE_URL = 'https://api.ameex.app';

    public const PATH_MASS_TRACKING = '/customer/Delivery/Parcels/MassTracking';

    public const PATH_MASS_INFO = '/customer/Delivery/Parcels/MassInfo';

    public const PATH_STATUS_LIST = '/customer/Delivery/Parcels/Statuts';

    public const PATH_RELAUNCH = '/customer/Delivery/Parcels/Action/Type/Relaunch';

    public const PATH_RELAUNCH_NEW = '/customer/Delivery/Parcels/Action/Type/RelaunchNew';

    public const PATH_CREATE_ORDER_SETTING = 'create_order_path';

    /** @return list<string> */
    public function suggestedProductsPaths(DeliveryCompany $company): array
    {
        $configured = trim($this->path($company, 'products_list_path', ''));

        return array_values(array_unique(array_filter([
            $configured !== '' ? $configured : null,
            '/customer/Delivery/Products',
            '/customer/Products',
            '/customer/Delivery/Stocks/Products',
            '/customer/Stock/Products',
            '/customer/Delivery/Stock/Products',
        ])));
    }

    public function apiId(DeliveryCompany $company): ?string
    {
        if (filled($company->api_username)) {
            return $this->sanitizeCredential((string) $company->api_username);
        }

        $settings = $company->api_settings ?? [];
        $fromSettings = $settings['api_id'] ?? null;

        if (filled($fromSettings)) {
            return $this->sanitizeCredential((string) $fromSettings);
        }

        return null;
    }

    public function businessId(DeliveryCompany $company): ?string
    {
        $settings = $company->api_settings ?? [];
        $businessId = $settings['business_id'] ?? $settings['mdl_business'] ?? null;

        if (filled($businessId)) {
            return $this->sanitizeCredential((string) $businessId);
        }

        return null;
    }

    /** @return list<string> */
    public function suggestedBusinessesPaths(DeliveryCompany $company): array
    {
        $configured = trim($this->path($company, 'businesses_list_path', ''));

        return array_values(array_unique(array_filter([
            $configured !== '' ? $configured : null,
            '/customer/Delivery/Businesses',
            '/customer/Delivery/MyBusinesses',
            '/customer/Delivery/Hubs',
            '/customer/Delivery/Stocks/Hubs',
            '/customer/Delivery/Business',
            '/customer/Businesses',
        ])));
    }

    /** @return array{success: bool, businesses?: array<string, string>, message: string, raw?: array<string, mixed>|null} */
    public function syncBusinesses(DeliveryCompany $company): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        $lastRaw = null;
        $tested = [];

        foreach ($this->suggestedBusinessesPaths($company) as $path) {
            $tested[] = $path;

            try {
                $response = $this->ameexHttp($company)
                    ->get($this->baseUrl($company).$path);

                $json = $response->json() ?? [];
                $raw = is_array($json) ? $json : ['body' => $response->body()];
                $lastRaw = $raw;

                if (! $response->successful() || AmeexResponseParser::hasApiError($raw)) {
                    continue;
                }

                $businessesMap = AmeexResponseParser::normalizeBusinessesMap($raw);

                if ($businessesMap === []) {
                    continue;
                }

                $settings = $company->api_settings ?? [];
                $settings['businesses_list_path'] = $path;
                $settings['ameex_businesses'] = $raw;
                $settings['ameex_businesses_map'] = $businessesMap;
                $settings['ameex_businesses_synced_at'] = now()->toIso8601String();

                if (blank($settings['business_id'] ?? null) && count($businessesMap) === 1) {
                    $settings['business_id'] = (string) array_key_first($businessesMap);
                }

                $company->update(['api_settings' => $settings]);

                return [
                    'success' => true,
                    'businesses' => $businessesMap,
                    'message' => __('codflow.delivery.ameex_businesses_sync_success', ['count' => count($businessesMap)]),
                    'raw' => $raw,
                ];
            } catch (\Throwable $exception) {
                Log::warning('Ameex businesses sync failed', [
                    'company_id' => $company->id,
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'success' => false,
            'message' => __('codflow.delivery.ameex_businesses_sync_failed', ['paths' => implode(', ', $tested)]),
            'raw' => $lastRaw,
        ];
    }

    public function apiKey(DeliveryCompany $company): ?string
    {
        if (! filled($company->api_token)) {
            return null;
        }

        return $this->sanitizeCredential((string) $company->api_token);
    }

    /** @return array<string, string> */
    public function authHeaders(DeliveryCompany $company, bool $json = true): array
    {
        $headers = [
            'C-Api-Id' => (string) $this->apiId($company),
            'C-Api-Key' => (string) $this->apiKey($company),
        ];

        if ($json) {
            $headers['Accept'] = 'application/json';
            $headers['Content-Type'] = 'application/json';
        } else {
            $headers['Accept'] = 'application/json,text/html,*/*';
        }

        return $headers;
    }

    public function isConfigured(DeliveryCompany $company): bool
    {
        return filled($this->apiId($company)) && filled($this->apiKey($company));
    }

    public function path(DeliveryCompany $company, string $key, string $default): string
    {
        return (string) (($company->api_settings ?? [])[$key] ?? $default);
    }

    /** @return array{success: bool, message: string, raw?: array<string, mixed>|null} */
    public function testConnection(DeliveryCompany $company): array
    {
        $result = $this->getParcelStatuses($company);

        if (! $result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'message' => __('codflow.delivery.ameex_connection_success'),
            'raw' => $result['raw'] ?? null,
        ];
    }

    /** @return array{success: bool, statuses?: array<string, mixed>, message: string, raw?: array<string, mixed>|null} */
    public function getParcelStatuses(DeliveryCompany $company): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        try {
            $path = $this->path($company, 'status_list_path', self::PATH_STATUS_LIST);
            $response = $this->ameexHttp($company)
                ->get($this->baseUrl($company).$path);

            $json = $response->json() ?? [];

            if (! $response->successful()) {
                return $this->fail('Ameex status list failed', [
                    'message' => $this->parseErrorMessage($json, __('codflow.delivery.api_error')),
                    'raw' => $json,
                ]);
            }

            if (! is_array($json) || $json === []) {
                return ['success' => false, 'message' => __('codflow.delivery.ameex_invalid_response'), 'raw' => $json];
            }

            if (AmeexResponseParser::hasApiError($json)) {
                return $this->fail('Ameex status list auth failed', [
                    'message' => AmeexResponseParser::extractApiMessage($json, __('codflow.delivery.ameex_login_error')),
                    'raw' => $json,
                ]);
            }

            $settings = $company->api_settings ?? [];
            $settings['ameex_status_list'] = $json;
            $settings['ameex_status_list_synced_at'] = now()->toIso8601String();
            $company->update(['api_settings' => $settings]);

            return [
                'success' => true,
                'statuses' => $json,
                'message' => __('codflow.delivery.ameex_status_sync_success'),
                'raw' => $json,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex status list exception', $exception);
        }
    }

    /** @return array{success: bool, products?: array<string, mixed>, path?: string, message: string, raw?: array<string, mixed>|null} */
    public function testProductsEndpoint(DeliveryCompany $company): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        $lastRaw = null;
        $tested = [];

        foreach ($this->suggestedProductsPaths($company) as $path) {
            $tested[] = $path;

            try {
                $response = $this->ameexHttp($company)
                    ->get($this->baseUrl($company).$path);

                $json = $response->json() ?? [];
                $raw = is_array($json) ? $json : ['body' => $response->body()];
                $lastRaw = $raw;

                if (! $response->successful() || AmeexResponseParser::hasApiError($raw)) {
                    continue;
                }

                if (! is_array($raw) || $raw === []) {
                    continue;
                }

                $settings = $company->api_settings ?? [];
                $settings['products_list_path'] = $path;
                $settings['ameex_products'] = $raw;
                $settings['ameex_products_synced_at'] = now()->toIso8601String();
                $company->update(['api_settings' => $settings]);

                return [
                    'success' => true,
                    'products' => $raw,
                    'path' => $path,
                    'message' => __('codflow.delivery.ameex_products_test_success', ['path' => $path]),
                    'raw' => $raw,
                ];
            } catch (\Throwable $exception) {
                Log::warning('Ameex products endpoint test failed', [
                    'company_id' => $company->id,
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'success' => false,
            'message' => __('codflow.delivery.ameex_products_test_failed', ['paths' => implode(', ', $tested)]),
            'raw' => $lastRaw,
        ];
    }

    /**
     * @param  array<int, string>  $codes
     * @return array{success: bool, parcels?: array<int, array<string, mixed>>, message: string, raw?: array<string, mixed>|null}
     */
    public function massTracking(DeliveryCompany $company, array $codes): array
    {
        return $this->postCodes($company, 'track_parcel_path', self::PATH_MASS_TRACKING, $codes, 'Ameex mass tracking');
    }

    /**
     * @param  array<int, string>  $codes
     * @return array{success: bool, parcels?: array<int, array<string, mixed>>, message: string, raw?: array<string, mixed>|null}
     */
    public function getMassInfo(DeliveryCompany $company, array $codes): array
    {
        return $this->postCodes($company, 'info_parcel_path', self::PATH_MASS_INFO, $codes, 'Ameex mass info');
    }

    /** @return array{success: bool, status?: string, parcel?: array<string, mixed>, message: string, raw?: array<string, mixed>} */
    public function refreshTracking(DeliveryCompany $company, Shipment $shipment): array
    {
        if (blank($shipment->tracking_number)) {
            return ['success' => false, 'message' => __('codflow.delivery.no_tracking_number')];
        }

        $result = $this->massTracking($company, [$shipment->tracking_number]);

        if (! $result['success']) {
            return $result;
        }

        $parcel = AmeexResponseParser::findParcelByCode($result['raw'] ?? [], $shipment->tracking_number);

        if (! $parcel) {
            return [
                'success' => false,
                'message' => __('codflow.delivery.ameex_parcel_not_found'),
                'raw' => $result['raw'] ?? null,
            ];
        }

        $fields = AmeexResponseParser::extractTrackingFields($parcel);

        return [
            'success' => true,
            'status' => $fields['statut'] ?? $fields['statut_name'],
            'parcel' => $fields,
            'message' => __('codflow.delivery.tracking_refreshed'),
            'raw' => $result['raw'] ?? null,
        ];
    }

    /** @return array{success: bool, message: string, raw?: array<string, mixed>|null} */
    public function relaunchParcel(DeliveryCompany $company, string $parcelCode): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        if (blank($parcelCode)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_parcel_not_found')];
        }

        try {
            $path = $this->path($company, 'relaunch_parcel_path', self::PATH_RELAUNCH);
            $url = $this->baseUrl($company).$path.'?ParcelCode='.urlencode($parcelCode);

            $response = $this->ameexHttp($company)
                ->get($url);

            $json = $response->json() ?? [];

            if (! $response->successful()) {
                return $this->fail('Ameex relaunch failed', [
                    'message' => $this->parseErrorMessage($json, __('codflow.delivery.api_error')),
                    'raw' => $json,
                ]);
            }

            return [
                'success' => true,
                'message' => __('codflow.delivery.ameex_relaunch_success'),
                'raw' => $json,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex relaunch exception', $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $customerData
     * @return array{success: bool, message: string, raw?: array<string, mixed>|null}
     */
    public function relaunchParcelNewCustomer(DeliveryCompany $company, string $parcelCode, array $customerData): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        if (blank($parcelCode)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_parcel_not_found')];
        }

        try {
            $path = $this->path($company, 'relaunch_new_parcel_path', self::PATH_RELAUNCH_NEW);
            $url = $this->baseUrl($company).$path.'?ParcelCode='.urlencode($parcelCode);

            $response = $this->ameexHttp($company)
                ->asMultipart()
                ->post($url, [
                    ['name' => 'order_num', 'contents' => (string) ($customerData['order_num'] ?? '')],
                    ['name' => 'receiver', 'contents' => (string) ($customerData['receiver'] ?? '')],
                    ['name' => 'phone', 'contents' => (string) ($customerData['phone'] ?? '')],
                    ['name' => 'city', 'contents' => (string) ($customerData['city'] ?? '')],
                    ['name' => 'address', 'contents' => (string) ($customerData['address'] ?? '')],
                    ['name' => 'comment', 'contents' => (string) ($customerData['comment'] ?? '')],
                    ['name' => 'price', 'contents' => (string) ($customerData['price'] ?? '')],
                ]);

            $json = $response->json() ?? [];

            if (! $response->successful()) {
                return $this->fail('Ameex relaunch new customer failed', [
                    'message' => $this->parseErrorMessage($json, __('codflow.delivery.api_error')),
                    'raw' => $json,
                ]);
            }

            return [
                'success' => true,
                'message' => __('codflow.delivery.ameex_relaunch_new_success'),
                'raw' => $json,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex relaunch new customer exception', $exception);
        }
    }

    /** @return array{success: bool, cities?: array<string, mixed>, message: string, raw?: array<string, mixed>|null} */
    public function syncCities(DeliveryCompany $company): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        $path = $this->path($company, 'cities_list_path', '/customer/Delivery/Cities');

        try {
            $response = $this->ameexHttp($company)
                ->get($this->baseUrl($company).$path);

            $json = $response->json() ?? [];

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'message' => __('codflow.delivery.ameex_cities_not_available'),
                    'raw' => $json,
                ];
            }

            if (! $response->successful()) {
                return $this->fail('Ameex cities sync failed', [
                    'message' => $this->parseErrorMessage($json, __('codflow.delivery.ameex_cities_not_available')),
                    'raw' => $json,
                ]);
            }

            $citiesMap = AmeexResponseParser::normalizeCitiesMap($json);

            if ($citiesMap === []) {
                return ['success' => false, 'message' => __('codflow.delivery.ameex_invalid_response'), 'raw' => $json];
            }

            $settings = $company->api_settings ?? [];
            $settings['ameex_cities'] = $json;
            $settings['ameex_cities_map'] = $citiesMap;
            $settings['ameex_cities_synced_at'] = now()->toIso8601String();
            $company->update(['api_settings' => $settings]);

            return [
                'success' => true,
                'cities' => $citiesMap,
                'message' => __('codflow.delivery.ameex_cities_sync_success'),
                'raw' => $json,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex cities sync exception', $exception);
        }
    }

    public function buildDeliveryNotePrintUrl(DeliveryCompany $company, string $reference): string
    {
        return rtrim($this->baseUrl($company), '/')
            .'/customer/Delivery/DeliveryNotes/Print/Type/Note?Ref='.urlencode($reference);
    }

    /** @return array{success: bool, html?: string, message: string, raw?: array<string, mixed>|null} */
    public function printDeliveryNoteHtml(DeliveryCompany $company, string $reference): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        if (blank($reference)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_ref_missing')];
        }

        try {
            $response = $this->ameexHttp($company)
                ->get($this->buildDeliveryNotePrintUrl($company, $reference));

            $json = $response->json();

            if (! $response->successful()) {
                return $this->fail('Ameex print delivery note failed', [
                    'message' => $this->parseErrorMessage(is_array($json) ? $json : null, __('codflow.delivery.api_error')),
                    'raw' => is_array($json) ? $json : null,
                ]);
            }

            return [
                'success' => true,
                'html' => $response->body(),
                'message' => __('codflow.delivery.ameex_print_success'),
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex print delivery note exception', $exception);
        }
    }

    /** @return array{success: bool, request_ref?: string, status?: string, message: string, raw?: array<string, mixed>|null} */
    public function createPickupRequest(DeliveryCompany $company, PickupRequest $pickup): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        if (blank($pickup->ameex_city_id)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_city_missing')];
        }

        if (blank($pickup->pickup_address) || blank($pickup->pickup_phone)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_pickup_fields_missing')];
        }

        $businessId = $this->businessId($company);

        if (blank($businessId)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_business_missing')];
        }

        $cityId = $this->resolveCityId($company, $pickup->ameex_city_id);

        if (blank($cityId)) {
            return [
                'success' => false,
                'message' => __('codflow.delivery.ameex_city_not_found', ['city' => $pickup->ameex_city_id ?? '']),
            ];
        }

        try {
            $url = rtrim($this->baseUrl($company), '/').'/customer/Delivery/PickupRequests/Action/Type/Add';

            $response = $this->ameexHttp($company)
                ->asMultipart()
                ->post($url, [
                    ['name' => 'mdl_business', 'contents' => $businessId],
                    ['name' => 'mdl_type', 'contents' => 'PARCEL_M'],
                    ['name' => 'mdl_city', 'contents' => $cityId],
                    ['name' => 'p_address', 'contents' => (string) $pickup->pickup_address],
                    ['name' => 'p_phone', 'contents' => (string) $pickup->pickup_phone],
                    ['name' => 'p_note', 'contents' => (string) ($pickup->notes ?? '')],
                ]);

            $json = $response->json() ?? [];
            $raw = is_array($json) ? $json : ['body' => $response->body()];

            if (! $response->successful() || AmeexResponseParser::hasApiError($raw)) {
                $pickup->update(['ameex_raw_response' => $raw]);

                return $this->fail('Ameex pickup request failed', [
                    'message' => AmeexResponseParser::extractApiMessage($raw, $this->parseErrorMessage($raw, __('codflow.delivery.api_error'))),
                    'raw' => $raw,
                ]);
            }

            $requestRef = AmeexResponseParser::extractPickupRequestRef($raw);
            $status = AmeexResponseParser::extractPickupStatus($raw);
            $hadApiRef = filled($requestRef);

            if (blank($requestRef) && ! AmeexResponseParser::isApiSuccess($raw)) {
                $pickup->update(['ameex_raw_response' => $raw]);

                return $this->fail('Ameex pickup request missing reference', [
                    'message' => __('codflow.delivery.ameex_pickup_invalid_response'),
                    'raw' => $raw,
                ]);
            }

            if (blank($requestRef)) {
                $requestRef = sprintf('CODFLOW-PR-%d-%s', $pickup->id, now()->format('YmdHis'));
            }

            $pickup->update([
                'ameex_city_id' => $cityId,
                'ameex_request_ref' => $requestRef,
                'ameex_status' => $status ?? 'pending',
                'ameex_raw_response' => $raw,
            ]);

            $message = $hadApiRef
                ? __('codflow.delivery.ameex_pickup_success')
                : __('codflow.delivery.ameex_pickup_success_no_ref');

            return [
                'success' => true,
                'request_ref' => $requestRef,
                'status' => $status ?? 'pending',
                'message' => $message,
                'raw' => $raw,
            ];
        } catch (\Throwable $exception) {
            $pickup->update(['ameex_raw_response' => ['error' => $exception->getMessage()]]);

            return $this->exceptionFail('Ameex pickup request exception', $exception);
        }
    }

    /** @return array{success: bool, tracking_number?: string, delivery_note_ref?: string, message: string, raw?: array<string, mixed>} */
    public function createShipment(DeliveryCompany $company, Order $order, Shipment $shipment): array
    {
        $validation = $this->validateCreateShipmentOrder($company, $order);

        if (! $validation['success']) {
            return $validation;
        }

        try {
            $path = $this->path($company, 'create_parcel_path', '/customer/Delivery/Parcels/Action/Type/Add');
            $multipart = $this->buildCreateShipmentPayload($company, $order, (string) $validation['business_id'], (string) $validation['city_id']);

            if (config('app.debug')) {
                Log::debug('Ameex create parcel payload', [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'payload' => $this->multipartToLoggablePayload($multipart),
                ]);
            }

            $response = $this->ameexHttp($company)
                ->asMultipart()
                ->post($this->baseUrl($company).$path, $multipart);

            $json = $response->json() ?? [];
            $raw = is_array($json) ? $json : ['body' => $response->body()];

            if (! $response->successful() || AmeexResponseParser::hasApiError($raw)) {
                return $this->fail('Ameex create parcel failed', [
                    'message' => AmeexResponseParser::extractApiMessage($raw, $this->parseErrorMessage(is_array($json) ? $json : null, __('codflow.delivery.api_error'))),
                    'raw' => $raw,
                ]);
            }

            $tracking = AmeexResponseParser::extractCreatedParcelCode($raw);

            if (blank($tracking)) {
                return $this->fail('Ameex create parcel missing tracking code', [
                    'message' => __('codflow.delivery.ameex_invalid_response'),
                    'raw' => $raw,
                ]);
            }

            return [
                'success' => true,
                'tracking_number' => $tracking,
                'delivery_note_ref' => $this->extractDeliveryNoteRef($raw),
                'message' => AmeexResponseParser::extractApiMessage($raw, __('codflow.delivery.ameex_send_success')),
                'raw' => $raw,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex create parcel exception', $exception);
        }
    }

    /** @return array{success: bool, message: string, raw?: array<string, mixed>|null} */
    public function createAmeexOrder(DeliveryCompany $company, Shipment $shipment): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        $path = trim($this->path($company, self::PATH_CREATE_ORDER_SETTING, ''));

        if ($path === '') {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_order_endpoint_missing')];
        }

        $order = $shipment->order?->loadMissing(['client', 'items.product']);

        if (! $order) {
            return ['success' => false, 'message' => __('codflow.delivery.no_order_for_shipment')];
        }

        $businessId = $this->businessId($company);

        if (blank($businessId)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_business_missing')];
        }

        if (blank($order->client?->full_name) || blank($order->client?->phone) || blank($order->address)) {
            $missing = array_values(array_filter([
                blank($order->client?->full_name) ? __('codflow.fields.client') : null,
                blank($order->client?->phone) ? __('codflow.fields.phone') : null,
                blank($order->address) ? __('codflow.fields.address') : null,
            ]));

            return [
                'success' => false,
                'message' => __('codflow.delivery.ameex_missing_fields', ['fields' => implode(', ', $missing)]),
            ];
        }

        if ($order->items->isEmpty()) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_no_items')];
        }

        $stockValidation = $this->validateOrderStockItems($order);

        if ($stockValidation !== null) {
            return ['success' => false, 'message' => $stockValidation];
        }

        try {
            $multipart = $this->buildCreateAmeexOrderPayload($company, $shipment, $order, (string) $businessId);

            if (config('app.debug')) {
                Log::debug('Ameex create order payload', [
                    'shipment_id' => $shipment->id,
                    'order_id' => $order->id,
                    'payload' => $this->multipartToLoggablePayload($multipart),
                ]);
            }

            $response = $this->ameexHttp($company)
                ->asMultipart()
                ->post($this->baseUrl($company).$path, $multipart);

            $json = $response->json() ?? [];
            $raw = is_array($json) ? $json : ['body' => $response->body()];

            $shipment->update(['ameex_raw_response' => $raw]);

            if (! $response->successful() || AmeexResponseParser::hasApiError($raw)) {
                return $this->fail('Ameex create order failed', [
                    'message' => AmeexResponseParser::extractApiMessage($raw, $this->parseErrorMessage(is_array($json) ? $json : null, __('codflow.delivery.api_error'))),
                    'raw' => $raw,
                ]);
            }

            return [
                'success' => true,
                'message' => AmeexResponseParser::extractApiMessage($raw, __('codflow.delivery.ameex_order_send_success')),
                'raw' => $raw,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail('Ameex create order exception', $exception);
        }
    }

    /**
     * @return list<array{name: string, contents: mixed}>
     */
    public function buildCreateAmeexOrderPayload(DeliveryCompany $company, Shipment $shipment, Order $order, string $businessId): array
    {
        $order->loadMissing(['client', 'items.product']);

        $multipart = [
            ['name' => 'business', 'contents' => $businessId],
            ['name' => 'order_num', 'contents' => (string) $order->order_number],
            ['name' => 'tracking_number', 'contents' => (string) $shipment->tracking_number],
            ['name' => 'receiver', 'contents' => (string) $order->client->full_name],
            ['name' => 'phone', 'contents' => (string) $order->client->phone],
            ['name' => 'city', 'contents' => (string) $order->city],
            ['name' => 'address', 'contents' => (string) $order->address],
            ['name' => 'comment', 'contents' => (string) ($order->notes ?? '')],
            ['name' => 'product', 'contents' => $this->productSummary($order)],
            ['name' => 'cod', 'contents' => (string) $order->final_amount],
        ];

        return $this->withProductReferences($multipart, $order);
    }

    /**
     * @return array{success: bool, message?: string, business_id?: string, city_id?: string}
     */
    public function validateCreateShipmentOrder(DeliveryCompany $company, Order $order): array
    {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        $businessId = $this->businessId($company);

        if (blank($businessId)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_business_missing')];
        }

        $order->loadMissing(['client', 'items.product']);

        if ($order->items->isEmpty()) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_no_items')];
        }

        $stockValidation = $this->validateOrderStockItems($order);

        if ($stockValidation !== null) {
            return ['success' => false, 'message' => $stockValidation];
        }

        if (blank($order->client?->full_name) || blank($order->client?->phone) || blank($order->address)) {
            $missing = array_values(array_filter([
                blank($order->client?->full_name) ? __('codflow.fields.client') : null,
                blank($order->client?->phone) ? __('codflow.fields.phone') : null,
                blank($order->address) ? __('codflow.fields.address') : null,
            ]));

            return [
                'success' => false,
                'message' => __('codflow.delivery.ameex_missing_fields', ['fields' => implode(', ', $missing)]),
            ];
        }

        if (blank($order->city)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_city_missing')];
        }

        $cityId = $this->resolveCityId($company, $order->city);

        if (blank($cityId)) {
            return [
                'success' => false,
                'message' => blank($order->city)
                    ? __('codflow.delivery.ameex_city_missing')
                    : __('codflow.delivery.ameex_city_not_found', ['city' => $order->city ?? '']),
            ];
        }

        return [
            'success' => true,
            'business_id' => (string) $businessId,
            'city_id' => (string) $cityId,
        ];
    }

    /**
     * STOCK mode payload for Ameex parcel creation.
     * Uses product references (products[n][ref]), never internal product IDs.
     *
     * @return list<array{name: string, contents: mixed}>
     */
    public function buildCreateShipmentPayload(DeliveryCompany $company, Order $order, string $businessId, string $cityId): array
    {
        $order->loadMissing(['client', 'items.product']);
        $settings = $company->api_settings ?? [];
        $productSummary = $this->productSummary($order);
        $multipart = [
            ['name' => 'type', 'contents' => 'STOCK'],
            ['name' => 'business', 'contents' => $businessId],
            ['name' => 'order_num', 'contents' => (string) $order->order_number],
            ['name' => 'replace', 'contents' => 'false'],
            ['name' => 'exchange_code', 'contents' => ''],
            ['name' => 'open', 'contents' => $this->yesNoSetting($settings['open'] ?? $settings['open_parcel'] ?? 'YES')],
            ['name' => 'try', 'contents' => 'NO'],
            ['name' => 'fragile', 'contents' => $this->boolIntSetting($settings['fragile'] ?? 0)],
            ['name' => 'receiver', 'contents' => (string) $order->client->full_name],
            ['name' => 'phone', 'contents' => (string) $order->client->phone],
            ['name' => 'city', 'contents' => $cityId],
            ['name' => 'address', 'contents' => (string) $order->address],
            ['name' => 'comment', 'contents' => (string) ($order->notes ?? '')],
            ['name' => 'product', 'contents' => $productSummary],
            ['name' => 'cod', 'contents' => (string) $order->final_amount],
            ['name' => 'staff', 'contents' => ''],
        ];

        return $this->withProductReferences($multipart, $order);
    }

    /**
     * Append STOCK-mode product lines: products[n][ref] + products[n][qty].
     * Never sends products[n][id] (SIMPLE mode only).
     *
     * @param  list<array{name: string, contents: mixed}>  $multipart
     * @return list<array{name: string, contents: mixed}>
     */
    protected function withProductReferences(array $multipart, Order $order): array
    {
        foreach ($order->items->values() as $index => $item) {
            $product = $item->product;

            if ($product === null) {
                continue;
            }

            // STOCK mode: products[n][ref] only — never products[n][id] (SIMPLE mode).
            $reference = filled(trim((string) $product->ameex_reference))
                ? trim((string) $product->ameex_reference)
                : trim((string) $product->sku);

            if (blank($reference)) {
                continue;
            }

            $multipart[] = ['name' => "products[{$index}][ref]", 'contents' => $reference];
            $multipart[] = ['name' => "products[{$index}][qty]", 'contents' => (string) $item->quantity];
        }

        return $multipart;
    }

    /**
     * Validate order items for Ameex STOCK mode.
     * CODFlow stock uses product_id internally; Ameex expects product references externally.
     */
    public function validateOrderStockItems(Order $order): ?string
    {
        foreach ($order->items as $item) {
            if ($item->product === null) {
                return __('codflow.delivery.ameex_item_product_missing');
            }

            if (blank($item->product->ameexStockReference())) {
                return __('codflow.delivery.ameex_product_ref_missing');
            }
        }

        return null;
    }

    protected function productSummary(Order $order): string
    {
        return $order->items
            ->map(fn ($item): string => trim(($item->product?->name ?? __('codflow.delivery.ameex_default_product')).' x'.$item->quantity))
            ->filter()
            ->implode(', ');
    }

    protected function yesNoSetting(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'YES' : 'NO';
        }

        return in_array(strtoupper((string) $value), ['1', 'TRUE', 'YES', 'OUI'], true) ? 'YES' : 'NO';
    }

    protected function boolIntSetting(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return in_array(strtoupper((string) $value), ['1', 'TRUE', 'YES', 'OUI'], true) ? '1' : '0';
    }

    /**
     * @param  list<array{name: string, contents: mixed}>  $multipart
     * @return array<string, mixed>
     */
    public function multipartToLoggablePayload(array $multipart): array
    {
        $payload = [];

        foreach ($multipart as $part) {
            $payload[$part['name']] = $part['contents'];
        }

        return $payload;
    }

    public function resolveCityId(DeliveryCompany $company, ?string $cityName): ?string
    {
        $settings = $company->api_settings ?? [];

        if (blank($cityName) && filled($settings['default_city_id'] ?? null)) {
            return (string) $settings['default_city_id'];
        }

        if (blank($cityName)) {
            return null;
        }

        if (ctype_digit(trim($cityName))) {
            return trim($cityName);
        }

        $citiesMap = $settings['ameex_cities_map'] ?? null;

        if (! is_array($citiesMap) || $citiesMap === []) {
            $sync = $this->syncCities($company);
            $citiesMap = is_array($sync['cities'] ?? null) ? $sync['cities'] : [];
        }

        if ($citiesMap === []) {
            return null;
        }

        $needle = $this->normalizeCityName($cityName);

        foreach ($citiesMap as $id => $name) {
            if ($this->normalizeCityName((string) $name) === $needle) {
                return (string) $id;
            }
        }

        foreach ($citiesMap as $id => $name) {
            $normalizedName = $this->normalizeCityName((string) $name);

            if (str_contains($normalizedName, $needle) || str_contains($needle, $normalizedName)) {
                return (string) $id;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $codes
     * @return array{success: bool, parcels?: array<int, array<string, mixed>>, message: string, raw?: array<string, mixed>|null}
     */
    protected function postCodes(
        DeliveryCompany $company,
        string $pathKey,
        string $defaultPath,
        array $codes,
        string $logContext,
    ): array {
        if (! $this->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config')];
        }

        $codes = array_values(array_filter(array_map('trim', $codes)));
        $codes = array_slice(array_unique($codes), 0, 25);

        if ($codes === []) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_no_codes')];
        }

        try {
            $path = $this->path($company, $pathKey, $defaultPath);

            $response = $this->ameexHttp($company)
                ->asMultipart()
                ->post($this->baseUrl($company).$path, [
                    ['name' => 'codes', 'contents' => implode(',', $codes)],
                ]);

            $json = $response->json() ?? [];

            if (! $response->successful()) {
                return $this->fail($logContext.' failed', [
                    'message' => $this->parseErrorMessage($json, __('codflow.delivery.api_error')),
                    'raw' => $json,
                ]);
            }

            if (AmeexResponseParser::isEmptyResponse($json)) {
                return ['success' => false, 'message' => __('codflow.delivery.ameex_invalid_response'), 'raw' => $json];
            }

            $parcels = AmeexResponseParser::normalizeParcelList($json);

            return [
                'success' => true,
                'parcels' => array_map(fn (array $p) => AmeexResponseParser::extractTrackingFields($p), $parcels),
                'message' => __('codflow.delivery.tracking_refreshed'),
                'raw' => $json,
            ];
        } catch (\Throwable $exception) {
            return $this->exceptionFail($logContext.' exception', $exception);
        }
    }

    protected function baseUrl(DeliveryCompany $company): string
    {
        return rtrim((string) ($company->api_base_url ?: $company->api_url ?: self::DEFAULT_BASE_URL), '/');
    }

    protected function ameexHttp(DeliveryCompany $company, bool $json = false): \Illuminate\Http\Client\PendingRequest
    {
        return Http::connectTimeout(15)
            ->timeout(60)
            ->retry(2, 1000, throw: false)
            ->withHeaders($this->authHeaders($company, $json));
    }

    /** @param  array<string, mixed>  $payload */
    protected function fail(string $logMessage, array $payload): array
    {
        Log::warning($logMessage, $payload);

        return [
            'success' => false,
            'message' => $payload['message'] ?? __('codflow.delivery.ameex_api_error_logs'),
            'raw' => $payload['raw'] ?? null,
        ];
    }

    /** @return array{success: false, message: string} */
    protected function exceptionFail(string $logMessage, \Throwable $exception): array
    {
        Log::error($logMessage, ['error' => $exception->getMessage()]);

        return [
            'success' => false,
            'message' => $this->resolveExceptionMessage($exception),
        ];
    }

    protected function resolveExceptionMessage(\Throwable $exception): string
    {
        $error = $exception->getMessage();

        if (str_contains($error, 'cURL error 28') || str_contains($error, 'SSL connection timeout')) {
            return __('codflow.delivery.ameex_network_timeout');
        }

        if (str_contains($error, 'cURL error 56') || str_contains($error, 'Connection reset')) {
            return __('codflow.delivery.ameex_network_reset');
        }

        if (str_contains($error, 'cURL error')) {
            return __('codflow.delivery.ameex_network_error');
        }

        return __('codflow.delivery.ameex_api_error_logs');
    }

    /** @param  array<string, mixed>|null  $json */
    protected function parseErrorMessage(?array $json, string $fallback): string
    {
        if (! is_array($json)) {
            return $fallback;
        }

        foreach (['message', 'Message', 'error', 'Error', 'msg'] as $key) {
            if (filled($json[$key] ?? null) && is_string($json[$key])) {
                return $json[$key];
            }
        }

        if (isset($json['data']) && is_array($json['data'])) {
            return $this->parseErrorMessage($json['data'], $fallback);
        }

        return $fallback;
    }

    /** @param  array<string, mixed>  $json */
    protected function extractDeliveryNoteRef(array $json): ?string
    {
        foreach (['Ref', 'ref', 'delivery_note_ref', 'DeliveryNoteRef', 'note_ref'] as $key) {
            if (filled($json[$key] ?? null)) {
                return (string) $json[$key];
            }
        }

        if (isset($json['data']) && is_array($json['data'])) {
            return $this->extractDeliveryNoteRef($json['data']);
        }

        return null;
    }

    /** @param  array<string, mixed>  $json */
    protected function extractPickupRequestRef(array $json): ?string
    {
        foreach (['ref', 'Ref', 'request_ref', 'id', 'ID', 'pickup_id'] as $key) {
            if (filled($json[$key] ?? null)) {
                return (string) $json[$key];
            }
        }

        return isset($json['data']) && is_array($json['data']) ? $this->extractPickupRequestRef($json['data']) : null;
    }

    /** @param  array<string, mixed>  $json */
    protected function extractPickupStatus(array $json): ?string
    {
        foreach (['status', 'Status', 'state'] as $key) {
            if (filled($json[$key] ?? null)) {
                return (string) $json[$key];
            }
        }

        return isset($json['data']) && is_array($json['data']) ? $this->extractPickupStatus($json['data']) : null;
    }

    protected function sanitizeCredential(string $value): string
    {
        $value = trim($value);

        if (preg_match('/(?:C-Api-Id|C-Api-Key|api[_\s-]?(?:id|key))\s*[=:]\s*(\S+)/i', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    protected function normalizeCityName(string $city): string
    {
        $city = trim(mb_strtolower($city));
        $city = str_replace(['fès', 'fes', 'casa', 'casablanca'], ['fes', 'fes', 'casablanca', 'casablanca'], $city);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $city);

        return is_string($ascii) && $ascii !== '' ? preg_replace('/[^a-z0-9]+/', '', $ascii) ?? $city : $city;
    }
}
