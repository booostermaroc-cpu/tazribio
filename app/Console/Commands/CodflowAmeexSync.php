<?php

namespace App\Console\Commands;

use App\Services\AmeexImportService;
use Illuminate\Console\Command;

class CodflowAmeexSync extends Command
{
    protected $signature = 'codflow:ameex:sync {--company=}';

    protected $description = 'Synchronize Ameex tracking/info for known shipment tracking numbers';

    public function handle(AmeexImportService $importService): int
    {
        $companyId = $this->option('company');

        $company = $companyId
            ? \App\Models\DeliveryCompany::query()->find($companyId)
            : null;

        if ($companyId && ! $company) {
            $this->error('Transporteur introuvable.');

            return self::FAILURE;
        }

        $result = $importService->syncCompanyShipments($company);

        $result['success'] ? $this->info($result['message']) : $this->error($result['message']);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
