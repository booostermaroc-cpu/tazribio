<?php

namespace App\Console\Commands;

use App\Enums\DeliveryProvider;
use App\Models\DeliveryCompany;
use Illuminate\Console\Command;

class CodflowAmeexCleanupSettings extends Command
{
    protected $signature = 'codflow:ameex:cleanup-settings {company? : ID du transporteur Ameex}';

    protected $description = 'Supprime les réponses API Ameex volumineuses de api_settings (garde uniquement les maps)';

    /** @var list<string> */
    protected array $keysToRemove = [
        'ameex_cities',
        'ameex_businesses',
    ];

    public function handle(): int
    {
        $company = $this->resolveCompany();

        if ($company === null) {
            $this->error('Aucun transporteur Ameex trouvé.');

            return self::FAILURE;
        }

        $settings = is_array($company->api_settings) ? $company->api_settings : [];
        $before = strlen(json_encode($settings) ?: '');
        $removed = [];

        foreach ($this->keysToRemove as $key) {
            if (array_key_exists($key, $settings)) {
                unset($settings[$key]);
                $removed[] = $key;
            }
        }

        if ($removed === []) {
            $this->info("Transporteur #{$company->id} — rien à nettoyer.");

            return self::SUCCESS;
        }

        $company->update(['api_settings' => $settings]);
        $after = strlen(json_encode($settings) ?: '');

        $this->info("Transporteur #{$company->id} — {$company->name}");
        $this->line('Clés supprimées : '.implode(', ', $removed));
        $this->line("Taille api_settings : {$before} → {$after} octets");

        return self::SUCCESS;
    }

    protected function resolveCompany(): ?DeliveryCompany
    {
        $id = $this->argument('company');

        if ($id !== null) {
            return DeliveryCompany::query()
                ->where('provider', DeliveryProvider::Ameex)
                ->find($id);
        }

        return DeliveryCompany::query()
            ->where('provider', DeliveryProvider::Ameex)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
