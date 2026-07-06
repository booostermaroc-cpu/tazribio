<?php

namespace App\Console\Commands;

use App\Services\AmeexAutoSyncService;
use Illuminate\Console\Command;

class CodflowAmeexAutoSync extends Command
{
    protected $signature = 'codflow:ameex:auto-sync';

    protected $description = 'Synchronisation automatique Ameex (connexion, statuts, villes, colis)';

    public function handle(AmeexAutoSyncService $syncService): int
    {
        $result = $syncService->syncAll();

        $this->info(__('codflow.delivery.ameex_auto_sync_done', [
            'companies' => $result['companies'],
            'parcels' => $result['synced_parcels'],
            'dispatched' => $result['dispatched_orders'] ?? 0,
        ]));

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::SUCCESS;
    }
}
