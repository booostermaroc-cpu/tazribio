<?php

namespace App\Services\Delivery;

use App\Filament\Support\AmeexLabels;

class AmeexResponseParser
{
    /** @return array<int, array<string, mixed>> */
    public static function normalizeParcelList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        if (isset($raw['data']) && is_array($raw['data'])) {
            return self::normalizeParcelList($raw['data']);
        }

        if (array_is_list($raw)) {
            return array_values(array_filter($raw, 'is_array'));
        }

        $parcels = [];
        foreach ($raw as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $parcel = $value;
            if (! isset($parcel['Code']) && ! isset($parcel['code']) && is_string($key) && ! in_array($key, ['success', 'message', 'Message', 'error'], true)) {
                $parcel['Code'] = $key;
            }

            $parcels[] = $parcel;
        }

        return $parcels;
    }

    /** @param  array<string, mixed>  $raw */
    public static function findParcelByCode(array $raw, string $code): ?array
    {
        $code = trim($code);

        foreach (self::normalizeParcelList($raw) as $parcel) {
            $parcelCode = self::parcelCode($parcel);

            if ($parcelCode && strcasecmp($parcelCode, $code) === 0) {
                return $parcel;
            }
        }

        if (isset($raw[$code]) && is_array($raw[$code])) {
            return $raw[$code];
        }

        return null;
    }

    /** @param  array<string, mixed>  $parcel */
    public static function parcelCode(array $parcel): ?string
    {
        foreach (['Code', 'code', 'CODE', 'tracking_number', 'TrackingNumber', 'ParcelCode', 'parcel_code'] as $key) {
            if (filled($parcel[$key] ?? null)) {
                return (string) $parcel[$key];
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $parcel */
    public static function extractTrackingFields(array $parcel): array
    {
        return [
            'code' => self::parcelCode($parcel),
            'parcel_code' => self::firstValue($parcel, ['ParcelCode', 'parcel_code', 'Code', 'code']),
            'statut' => self::firstValue($parcel, ['STATUT', 'Statut', 'statut', 'status', 'Status']),
            'statut_name' => self::firstValue($parcel, ['STATUT_NAME', 'StatutName', 'statut_name', 'status_name', 'StatusName']),
            'statut_s' => self::firstValue($parcel, ['STATUT_S', 'StatutS', 'statut_s', 'sub_status']),
            'statut_s_name' => self::firstValue($parcel, ['STATUT_S_NAME', 'StatutSName', 'statut_s_name', 'sub_status_name']),
            'comment' => self::firstValue($parcel, ['COMMENT', 'Comment', 'comment']),
            'delivery_note_ref' => self::firstValue($parcel, ['Ref', 'ref', 'DeliveryNoteRef', 'delivery_note_ref', 'BL_Ref']),
            'date' => self::firstValue($parcel, ['DATE', 'Date', 'date', 'updated_at']),
        ];
    }

    public static function isEmptyResponse(mixed $raw): bool
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return true;
        }

        if (! is_array($raw)) {
            return false;
        }

        return self::normalizeParcelList($raw) === [] && ! isset($raw['message']) && ! isset($raw['Message']);
    }

    public static function hasApiError(mixed $raw): bool
    {
        if (! is_array($raw)) {
            return true;
        }

        if (($raw['login'] ?? null) === 'error') {
            return true;
        }

        $api = $raw['api'] ?? null;

        if (is_array($api) && (($api['type'] ?? null) === 'error' || ($api['Type'] ?? null) === 'error')) {
            return true;
        }

        foreach (['type', 'Type'] as $key) {
            if (($raw[$key] ?? null) === 'error') {
                return true;
            }
        }

        return isset($raw['success']) && $raw['success'] === false;
    }

    public static function isApiSuccess(mixed $raw): bool
    {
        if (! is_array($raw) || self::hasApiError($raw)) {
            return false;
        }

        $api = $raw['api'] ?? null;

        if (is_array($api)) {
            $type = $api['type'] ?? $api['Type'] ?? null;

            if (in_array($type, ['success', 'Success'], true)) {
                return true;
            }
        }

        if (isset($raw['success']) && $raw['success'] === true) {
            return true;
        }

        return filled(self::extractPickupRequestRef($raw));
    }

    public static function extractApiMessage(mixed $raw, string $fallback): string
    {
        if (! is_array($raw)) {
            return $fallback;
        }

        $message = null;
        $api = $raw['api'] ?? null;

        if (is_array($api)) {
            foreach (['msg', 'message', 'Message'] as $key) {
                if (filled($api[$key] ?? null) && is_string($api[$key])) {
                    $message = $api[$key];
                    break;
                }
            }
        }

        if ($message === null) {
            foreach (['message', 'Message', 'error', 'msg'] as $key) {
                if (filled($raw[$key] ?? null) && is_string($raw[$key])) {
                    $message = $raw[$key];
                    break;
                }
            }
        }

        if (($raw['login'] ?? null) === 'error') {
            return __('codflow.delivery.ameex_login_error');
        }

        if (is_string($message) && self::isSenderSelectionError($message)) {
            return AmeexLabels::delivery('ameex_sender_required');
        }

        if (is_string($message) && self::isInsufficientStockError($message)) {
            $product = trim(strip_tags($message));

            return __('codflow.delivery.ameex_stock_insufficient', ['product' => $product]);
        }

        if (is_string($message)) {
            return trim(strip_tags($message));
        }

        return $fallback;
    }

    public static function isSenderSelectionError(string $message): bool
    {
        $normalized = mb_strtolower($message);

        return str_contains($normalized, 'expéditeur')
            || str_contains($normalized, 'expediteur')
            || str_contains($normalized, 'choisir l');
    }

    public static function isInsufficientStockError(string $message): bool
    {
        $normalized = mb_strtolower($message);

        return str_contains($normalized, 'pas suffisante')
            || str_contains($normalized, 'insufficient')
            || str_contains($normalized, 'stock insuffisant');
    }

    public static function hasInsufficientStockError(mixed $raw): bool
    {
        $message = self::extractRawApiMessage($raw);

        return is_string($message) && self::isInsufficientStockError($message);
    }

    public static function extractRawApiMessage(mixed $raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }

        $api = $raw['api'] ?? null;

        if (is_array($api)) {
            foreach (['msg', 'message', 'Message'] as $key) {
                if (filled($api[$key] ?? null) && is_string($api[$key])) {
                    return $api[$key];
                }
            }
        }

        foreach (['message', 'Message', 'error', 'msg'] as $key) {
            if (filled($raw[$key] ?? null) && is_string($raw[$key])) {
                return $raw[$key];
            }
        }

        return null;
    }

    public static function extractPickupRequestRef(mixed $raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }

        foreach ([
            ['api', 'data', 'ref'],
            ['api', 'data', 'Ref'],
            ['api', 'data', 'id'],
            ['api', 'data', 'ID'],
            ['api', 'data', 'pickup_id'],
            ['data', 'ref'],
            ['data', 'id'],
        ] as $path) {
            $value = $raw;

            foreach ($path as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$segment];
            }

            if (filled($value)) {
                return (string) $value;
            }
        }

        foreach (['ref', 'Ref', 'request_ref', 'id', 'ID', 'pickup_id'] as $key) {
            if (filled($raw[$key] ?? null)) {
                return (string) $raw[$key];
            }
        }

        return null;
    }

    public static function extractPickupStatus(mixed $raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }

        foreach ([['api', 'data', 'status'], ['api', 'data', 'Status'], ['data', 'status']] as $path) {
            $value = $raw;

            foreach ($path as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$segment];
            }

            if (filled($value)) {
                return (string) $value;
            }
        }

        foreach (['status', 'Status', 'state'] as $key) {
            if (filled($raw[$key] ?? null)) {
                return (string) $raw[$key];
            }
        }

        return null;
    }

    public static function extractCreatedParcelCode(mixed $raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }

        foreach ([
            ['api', 'data', 'code'],
            ['api', 'data', 'Code'],
            ['data', 'code'],
            ['data', 'Code'],
        ] as $path) {
            $value = $raw;

            foreach ($path as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$segment];
            }

            if (filled($value) && is_string($value)) {
                return $value;
            }
        }

        foreach (['Code', 'code', 'tracking_number', 'TrackingNumber'] as $key) {
            if (filled($raw[$key] ?? null) && is_string($raw[$key])) {
                return $raw[$key];
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public static function normalizeCitiesMap(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $cities = $raw['api']['cities'] ?? $raw['cities'] ?? $raw;

        if (! is_array($cities)) {
            return [];
        }

        $map = [];

        foreach ($cities as $id => $city) {
            if (! is_array($city)) {
                continue;
            }

            $cityId = (string) ($city['id'] ?? $id);
            $name = (string) ($city['name'] ?? '');

            if ($cityId !== '' && $name !== '') {
                $map[$cityId] = $name;
            }
        }

        return $map;
    }

    /** @return array<string, string> */
    public static function normalizeBusinessesMap(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $candidates = [
            $raw['api']['businesses'] ?? null,
            $raw['api']['business'] ?? null,
            $raw['api']['data'] ?? null,
            $raw['api']['hubs'] ?? null,
            $raw['api']['list'] ?? null,
            $raw['businesses'] ?? null,
            $raw['hubs'] ?? null,
            $raw['data'] ?? null,
            $raw['list'] ?? null,
        ];

        $map = [];

        foreach ($candidates as $businesses) {
            if (! is_array($businesses)) {
                continue;
            }

            $map = array_merge($map, self::extractBusinessesFromList($businesses));
        }

        return self::sanitizeBusinessesMap($map);
    }

    /** @param  array<string, string>  $map */
    public static function sanitizeBusinessesMap(array $map): array
    {
        $metaIds = ['success', 'error', 'api', 'login', 'type', 'message', 'msg', 'data', 'list', 'hubs', 'businesses', 'body'];
        $metaNames = ['success', 'error', 'true', 'false', 'ok'];

        $clean = [];

        foreach ($map as $id => $name) {
            $id = trim((string) $id);
            $name = trim(strip_tags((string) $name));

            if ($id === '' || $name === '' || mb_strlen($name) < 3) {
                continue;
            }

            if (in_array(mb_strtolower($id), $metaIds, true)) {
                continue;
            }

            if (in_array(mb_strtolower($name), $metaNames, true)) {
                continue;
            }

            if (! ctype_digit($id)) {
                continue;
            }

            $clean[$id] = $name;
        }

        return $clean;
    }

    /** @return array<string, string> */
    protected static function extractBusinessesFromList(array $businesses): array
    {
        $map = [];

        foreach ($businesses as $id => $business) {
            if (is_string($business) && filled($business) && ! in_array((string) $id, ['success', 'message', 'Message', 'error', 'api', 'login', 'type', 'Type', 'msg', 'body', 'data'], true)) {
                if (! ctype_digit((string) $id)) {
                    continue;
                }

                $map[(string) $id] = $business;

                continue;
            }

            if (! is_array($business)) {
                continue;
            }

            $businessId = (string) (
                $business['id']
                ?? $business['ID']
                ?? $business['business_id']
                ?? $business['BusinessId']
                ?? $business['mdl_business']
                ?? $business['business']
                ?? $id
            );

            $name = (string) (
                $business['name']
                ?? $business['Name']
                ?? $business['label']
                ?? $business['title']
                ?? $business['business_name']
                ?? $business['BusinessName']
                ?? $business['hub_name']
                ?? $business['HubName']
                ?? $business['company']
                ?? $business['Company']
                ?? $business['sender']
                ?? $business['Sender']
                ?? ''
            );

            if ($businessId !== '' && $name !== '' && ! in_array($businessId, ['success', 'message', 'api', 'login'], true)) {
                $map[$businessId] = $name;
            }
        }

        return $map;
    }

    /** @param  array<string, mixed>  $data */
    protected static function firstValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($data[$key] ?? null)) {
                return (string) $data[$key];
            }
        }

        return null;
    }
}
