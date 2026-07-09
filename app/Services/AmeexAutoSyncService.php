<?php

namespace App\Services;

use App\Enums\DeliveryProvider;
use App\Models\DeliveryCompany;
use App\Services\Delivery\AmeexDeliveryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AmeexAutoSyncService
{
    public function __construct(
        protected AmeexDeliveryService $ameex,
        protected AmeexImportService $importService,
    ) {}

    /** @return array{companies: int, synced_parcels: int, dispatched_orders: int, errors: list<string>} */
    public function syncAll(): array
    {
        $companies = DeliveryCompany::query()
            ->where('provider', DeliveryProvider::Ameex)
            ->where('is_active', true)
            ->get();

        $syncedParcels = 0;
        $dispatchedOrders = 0;
        $errors = [];

        foreach ($companies as $company) {
            if (! $this->ameex->isConfigured($company)) {
                continue;
            }

            $connection = $this->ameex->testConnection($company);

            if (! $connection['success']) {
                $errors[] = "{$company->name}: ".$connection['message'];
                Log::warning('Ameex auto-sync connection test failed', [
                    'company_id' => $company->id,
                    'message' => $connection['message'],
                ]);
            } elseif ($this->shouldSyncReferenceData($company)) {
                $statuses = $this->ameex->getParcelStatuses($company);

                if (! $statuses['success']) {
                    $errors[] = "{$company->name} statuts: ".$statuses['message'];
                }

                $cities = $this->ameex->syncCities($company);

                if (! $cities['success']) {
                    $errors[] = "{$company->name} villes: ".$cities['message'];
                }
            }

            $parcels = $this->importService->syncCompanyShipments($company);

            if (! $parcels['success']) {
                $errors[] = "{$company->name} colis: ".$parcels['message'];
            } else {
                $syncedParcels += (int) ($parcels['synced'] ?? 0);
            }
        }

        return [
            'companies' => $companies->count(),
            'synced_parcels' => $syncedParcels,
            'dispatched_orders' => $dispatchedOrders,
            'errors' => $errors,
        ];
    }

    protected function shouldSyncReferenceData(DeliveryCompany $company): bool
    {
        $settings = $company->api_settings ?? [];

        if (! is_array($settings['ameex_cities_map'] ?? null) || $settings['ameex_cities_map'] === []) {
            return true;
        }

        if (blank($settings['ameex_status_list_synced_at'] ?? null)) {
            return true;
        }

        $syncedAt = $settings['ameex_cities_synced_at'] ?? null;

        if (blank($syncedAt)) {
            return true;
        }

        try {
            return Carbon::parse($syncedAt)->lt(now()->subDay());
        } catch (\Throwable) {
            return true;
        }
    }
}
