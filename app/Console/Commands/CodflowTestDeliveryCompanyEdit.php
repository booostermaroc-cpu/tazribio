<?php

namespace App\Console\Commands;

use App\Enums\DeliveryProvider;
use App\Filament\Resources\DeliveryCompanies\Concerns\InteractsWithAmeexBusinessIdForm;
use App\Models\DeliveryCompany;
use Illuminate\Console\Command;

class CodflowTestDeliveryCompanyEdit extends Command
{
    protected $signature = 'codflow:test:delivery-company-edit {company=2 : ID transporteur}';

    protected $description = 'Simule le chargement du formulaire transporteur (diagnostic 500)';

    public function handle(): int
    {
        $company = DeliveryCompany::query()
            ->where('provider', DeliveryProvider::Ameex)
            ->find($this->argument('company'));

        if ($company === null) {
            $this->error('Transporteur introuvable.');

            return self::FAILURE;
        }

        $tester = new class
        {
            use InteractsWithAmeexBusinessIdForm;

            public ?DeliveryCompany $record = null;

            public function run(DeliveryCompany $company): array
            {
                $this->record = $company;
                $this->cacheApiSettingsFromRecord();

                return $this->mutateFormDataBeforeFill($company->toArray());
            }
        };

        try {
            $filled = $tester->run($company->fresh());
            $settings = $company->api_settings ?? [];

            $this->info("Transporteur #{$company->id} — {$company->name}");
            $this->table(['Métrique', 'Valeur'], [
                ['api_settings DB (octets)', (string) strlen(json_encode($settings) ?: '')],
                ['villes map', (string) (is_array($settings['ameex_cities_map'] ?? null) ? count($settings['ameex_cities_map']) : 0)],
                ['hubs map', (string) (is_array($settings['ameex_businesses_map'] ?? null) ? count($settings['ameex_businesses_map']) : 0)],
                ['business_id', (string) ($filled['ameex_business_id'] ?? '(vide)')],
                ['api_settings dans forme', isset($filled['api_settings']) ? 'OUI (erreur)' : 'NON (OK)'],
                ['options JSON (octets)', (string) strlen((string) ($filled['ameex_businesses_options_json'] ?? ''))],
            ]);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            $this->line($exception->getTraceAsString());

            return self::FAILURE;
        }
    }
}
