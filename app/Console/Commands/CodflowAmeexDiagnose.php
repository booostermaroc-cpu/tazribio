<?php

namespace App\Console\Commands;

use App\Enums\DeliveryProvider;
use App\Models\DeliveryCompany;
use App\Services\Delivery\AmeexDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class CodflowAmeexDiagnose extends Command
{
    protected $signature = 'codflow:ameex:diagnose {company? : ID du transporteur Ameex (défaut: premier actif)}';

    protected $description = 'Diagnostic Ameex prod/local : credentials, réseau, villes, migration';

    public function handle(AmeexDeliveryService $ameex): int
    {
        $company = $this->resolveCompany();

        if ($company === null) {
            $this->error('Aucun transporteur Ameex trouvé.');

            return self::FAILURE;
        }

        $this->info("Transporteur #{$company->id} — {$company->name}");
        $this->newLine();

        $this->line('Environnement');
        $this->table(['Clé', 'Valeur'], [
            ['APP_URL', (string) config('app.url')],
            ['APP_ENV', (string) config('app.env')],
            ['APP_DEBUG', config('app.debug') ? 'true' : 'false'],
        ]);

        $settings = $company->api_settings ?? [];
        $apiIdFromSettings = filled($settings['api_id'] ?? null) ? (string) $settings['api_id'] : '(vide)';
        $resolvedApiId = $ameex->apiId($company) ?? '(manquant)';
        $tokenLen = filled($company->api_token) ? strlen((string) $company->api_token) : 0;
        $citiesCount = is_array($settings['ameex_cities_map'] ?? null) ? count($settings['ameex_cities_map']) : 0;

        $this->newLine();
        $this->line('Credentials (masqués)');
        $this->table(['Clé', 'Valeur'], [
            ['api_base_url', $company->api_base_url ?: AmeexDeliveryService::DEFAULT_BASE_URL],
            ['api_username (C-Api-Id)', $company->api_username ?: '(vide)'],
            ['api_settings.api_id', $apiIdFromSettings],
            ['api_id résolu', $resolvedApiId],
            ['api_token longueur', $tokenLen > 0 ? "{$tokenLen} caractères" : 'MANQUANT'],
            ['business_id résolu', $ameex->businessId($company) ?? '(manquant)'],
            ['is_configured', $ameex->isConfigured($company) ? 'oui' : 'NON'],
            ['villes synchronisées', (string) $citiesCount],
            ['colonne ameex_reference', Schema::hasColumn('products', 'ameex_reference') ? 'oui' : 'NON — lancer migrate'],
        ]);

        if ($apiIdFromSettings !== '(vide)' && $company->api_username && $apiIdFromSettings !== $company->api_username) {
            $this->warn('api_settings.api_id diffère de C-Api-Id : le champ C-Api-Id est prioritaire.');
        }

        if ($tokenLen === 0) {
            $this->error('Clé API absente en base. Ré-enregistrez C-Api-Key dans Transporteurs → AMEEX.');
        }

        $this->newLine();
        $this->line('Test réseau direct (curl Laravel)...');

        try {
            $baseUrl = rtrim((string) ($company->api_base_url ?: AmeexDeliveryService::DEFAULT_BASE_URL), '/');
            $url = $baseUrl.AmeexDeliveryService::PATH_STATUS_LIST;

            $started = microtime(true);
            $response = Http::connectTimeout(15)
                ->timeout(30)
                ->withHeaders($ameex->authHeaders($company))
                ->get($url);
            $elapsed = round((microtime(true) - $started) * 1000);

            $body = $response->json() ?? [];
            $preview = mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE) ?: $response->body(), 0, 200);

            $this->table(['Métrique', 'Valeur'], [
                ['URL', $url],
                ['HTTP', (string) $response->status()],
                ['Durée', "{$elapsed} ms"],
                ['Aperçu réponse', $preview],
            ]);
        } catch (\Throwable $exception) {
            $this->error('Échec réseau : '.$exception->getMessage());
        }

        $this->newLine();
        $this->line('Test via AmeexDeliveryService::testConnection()...');
        $result = $ameex->testConnection($company->fresh());

        if ($result['success']) {
            $this->info('OK — '.$result['message']);
        } else {
            $this->error('ÉCHEC — '.$result['message']);
        }

        $this->newLine();
        $this->line('Webhook entrant (optionnel) : POST '.url('/api/webhooks/ameex'));

        return $result['success'] ? self::SUCCESS : self::FAILURE;
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
