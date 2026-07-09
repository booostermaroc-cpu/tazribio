<?php

namespace App\Filament\Resources\DeliveryCompanies\Concerns;

trait InteractsWithAmeexBusinessIdForm
{
    /** @var array<string, mixed>|null */
    protected ?array $fullApiSettings = null;

    /** @var list<string> */
    protected array $ameexSyncPayloadKeys = [
        'ameex_cities',
        'ameex_cities_map',
        'ameex_cities_synced_at',
        'ameex_businesses',
        'ameex_businesses_map',
        'ameex_businesses_synced_at',
        'ameex_products',
        'ameex_products_synced_at',
        'ameex_status_list',
        'ameex_status_list_synced_at',
        'ameex_statuses',
        'ameex_statuses_map',
        'ameex_statuses_synced_at',
    ];

    protected function cacheApiSettingsFromRecord(): void
    {
        $this->fullApiSettings = is_array($this->record?->api_settings)
            ? $this->record->api_settings
            : [];

        if ($this->record !== null) {
            $this->record->setAttribute('api_settings', null);
        }
    }

    protected function refreshCachedApiSettings(): void
    {
        $this->record?->refresh();
        $this->cacheApiSettingsFromRecord();
    }

    /** @return array<string, mixed> */
    protected function apiSettingsSource(): array
    {
        if (is_array($this->fullApiSettings)) {
            return $this->fullApiSettings;
        }

        return is_array($this->record?->api_settings) ? $this->record->api_settings : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $settings = $this->apiSettingsSource();
        $data['ameex_business_id'] = (string) ($settings['business_id'] ?? '');
        $data['ameex_send_without_stock'] = in_array(strtoupper((string) ($settings['send_without_stock_check'] ?? '0')), ['1', 'TRUE', 'YES', 'OUI'], true);
        $businessesMap = is_array($settings['ameex_businesses_map'] ?? null)
            ? $settings['ameex_businesses_map']
            : [];
        $data['ameex_businesses_options_json'] = json_encode($businessesMap, JSON_UNESCAPED_UNICODE) ?: '{}';
        $data['ameex_cities_count'] = is_array($settings['ameex_cities_map'] ?? null)
            ? count($settings['ameex_cities_map'])
            : 0;
        unset($data['api_settings']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $settings = $this->apiSettingsSource();

        if (filled($data['ameex_business_id'] ?? null)) {
            $settings['business_id'] = trim((string) $data['ameex_business_id']);
        }

        $settings['send_without_stock_check'] = ($data['ameex_send_without_stock'] ?? false) ? '1' : '0';

        unset($data['ameex_business_id'], $data['ameex_send_without_stock'], $data['ameex_businesses_options_json'], $data['ameex_cities_count']);

        $data['api_settings'] = $settings;
        $this->fullApiSettings = $settings;

        if (filled($data['api_base_url'] ?? null)) {
            $data['api_base_url'] = str_replace('http://api.ameex.app', 'https://api.ameex.app', (string) $data['api_base_url']);
        }

        return $data;
    }

    protected function isAmeexProvider(mixed $provider): bool
    {
        if ($provider instanceof \App\Enums\DeliveryProvider) {
            return $provider === \App\Enums\DeliveryProvider::Ameex;
        }

        return (string) $provider === \App\Enums\DeliveryProvider::Ameex->value;
    }
}
