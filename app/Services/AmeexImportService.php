<?php

namespace App\Services;

use App\Enums\DeliveryProvider;
use App\Models\DeliveryCompany;
use App\Models\Shipment;
use App\Services\Delivery\AmeexDeliveryService;
use App\Services\Delivery\AmeexResponseParser;

class AmeexImportService
{
    public function __construct(
        protected DeliveryIntegrationService $deliveryIntegrationService,
    ) {}

    /** @return array{success: bool, message: string, synced: int} */
    public function syncCompanyShipments(?DeliveryCompany $company = null): array
    {
        $company ??= DeliveryCompany::query()
            ->where('provider', DeliveryProvider::Ameex)
            ->where('is_active', true)
            ->first();

        if (! $company) {
            return ['success' => false, 'message' => __('codflow.delivery.no_company'), 'synced' => 0];
        }

        $ameex = app(AmeexDeliveryService::class);

        if (! $ameex->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config'), 'synced' => 0];
        }

        $shipments = Shipment::query()
            ->where('delivery_company_id', $company->id)
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNotNull('tracking_number')
                        ->where('tracking_number', 'not like', 'PENDING-%');
                })->orWhereNotNull('ameex_parcel_code');
            })
            ->whereNotIn('delivery_status', [
                \App\Enums\ShipmentStatus::Delivered->value,
                \App\Enums\ShipmentStatus::Returned->value,
            ])
            ->get();

        if ($shipments->isEmpty()) {
            return [
                'success' => true,
                'message' => __('codflow.delivery.ameex_import_no_tracking'),
                'synced' => 0,
            ];
        }

        $synced = 0;

        foreach ($shipments->chunk(25) as $chunk) {
            $codes = $chunk
                ->map(fn (Shipment $shipment): ?string => $shipment->ameex_parcel_code ?: $shipment->tracking_number)
                ->filter(fn (?string $code): bool => filled($code) && ! str_starts_with((string) $code, 'PENDING-'))
                ->values()
                ->all();

            if ($codes === []) {
                continue;
            }

            $tracking = $ameex->massTracking($company, $codes);
            $info = $ameex->getMassInfo($company, $codes);

            foreach ($chunk as $shipment) {
                $code = $shipment->ameex_parcel_code ?: $shipment->tracking_number;

                if (blank($code) || str_starts_with((string) $code, 'PENDING-')) {
                    continue;
                }
                $parcelRaw = AmeexResponseParser::findParcelByCode($tracking['raw'] ?? [], (string) $code)
                    ?? AmeexResponseParser::findParcelByCode($info['raw'] ?? [], (string) $code);

                if (! $parcelRaw) {
                    continue;
                }

                $fields = AmeexResponseParser::extractTrackingFields($parcelRaw);
                $raw = [
                    'tracking' => $tracking['raw'] ?? null,
                    'info' => $info['raw'] ?? null,
                ];

                $this->deliveryIntegrationService->applyAmeexParcelFields($shipment, $fields, $raw);
                $synced++;
            }
        }

        return [
            'success' => true,
            'message' => __('codflow.delivery.ameex_import_success', ['count' => $synced]),
            'synced' => $synced,
        ];
    }

    /**
     * Import by known tracking codes (CSV/manual list).
     *
     * @param  array<int, string>  $codes
     * @return array{success: bool, message: string, synced: int}
     */
    public function syncByCodes(DeliveryCompany $company, array $codes): array
    {
        $ameex = app(AmeexDeliveryService::class);

        if (! $ameex->isConfigured($company)) {
            return ['success' => false, 'message' => __('codflow.delivery.ameex_incomplete_config'), 'synced' => 0];
        }

        $synced = 0;
        $codes = array_values(array_filter(array_map('trim', $codes)));

        foreach (array_chunk($codes, 25) as $chunk) {
            $info = $ameex->getMassInfo($company, $chunk);
            $tracking = $ameex->massTracking($company, $chunk);

            foreach ($chunk as $code) {
                $parcelRaw = AmeexResponseParser::findParcelByCode($info['raw'] ?? [], $code)
                    ?? AmeexResponseParser::findParcelByCode($tracking['raw'] ?? [], $code);

                if (! $parcelRaw) {
                    continue;
                }

                $shipment = Shipment::query()->where('tracking_number', $code)->first();

                if (! $shipment) {
                    continue;
                }

                $this->deliveryIntegrationService->applyAmeexParcelFields(
                    $shipment,
                    AmeexResponseParser::extractTrackingFields($parcelRaw),
                    ['tracking' => $tracking['raw'] ?? null, 'info' => $info['raw'] ?? null],
                );
                $synced++;
            }
        }

        return [
            'success' => true,
            'message' => __('codflow.delivery.ameex_import_success', ['count' => $synced]),
            'synced' => $synced,
        ];
    }
}
