<?php

namespace App\Filament\Resources\DeliveryCompanies\Concerns;

trait InteractsWithAmeexBusinessIdForm
{
    /** @var list<string> */
    protected array $ameexSyncPayloadKeys = [
        'ameex_cities',
        'ameex_cities_map',
        'ameex_cities_synced_at',
        'ameex_businesses',
        'ameex_businesses_map',
        'ameex_businesses_synced_at',
        'ameex_statuses',
        'ameex_statuses_map',
        'ameex_statuses_synced_at',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $settings = is_array($data['api_settings'] ?? null) ? $data['api_settings'] : [];
        $data['ameex_business_id'] = (string) ($settings['business_id'] ?? '');
        $data['ameex_send_without_stock'] = in_array(strtoupper((string) ($settings['send_without_stock_check'] ?? '0')), ['1', 'TRUE', 'YES', 'OUI'], true);
        $data['ameex_businesses_options'] = is_array($settings['ameex_businesses_map'] ?? null)
            ? $settings['ameex_businesses_map']
            : [];
        $data['ameex_businesses_options_json'] = json_encode($data['ameex_businesses_options'], JSON_UNESCAPED_UNICODE) ?: '{}';
        $data['api_settings'] = $this->scalarApiSettings($settings);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $existingSettings = is_array($this->record?->api_settings) ? $this->record->api_settings : [];
        $preserved = $this->preservedApiSettings($existingSettings);
        $formScalars = $this->scalarApiSettings(is_array($data['api_settings'] ?? null) ? $data['api_settings'] : []);
        $settings = array_merge($preserved, $formScalars);

        if (filled($data['ameex_business_id'] ?? null)) {
            $settings['business_id'] = trim((string) $data['ameex_business_id']);
        }

        $settings['send_without_stock_check'] = ($data['ameex_send_without_stock'] ?? false) ? '1' : '0';

        unset($data['ameex_business_id'], $data['ameex_send_without_stock'], $data['ameex_businesses_options'], $data['ameex_businesses_options_json']);

        $data['api_settings'] = $settings;

        if (filled($data['api_base_url'] ?? null)) {
            $data['api_base_url'] = str_replace('http://api.ameex.app', 'https://api.ameex.app', (string) $data['api_base_url']);
        }

        return $data;
    }

    /**
     * Scalar settings only — safe for KeyValue field rendering.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    protected function scalarApiSettings(array $settings): array
    {
        return collect($settings)
            ->filter(function (mixed $value, mixed $key): bool {
                if (! is_string($key) || blank($key)) {
                    return false;
                }

                if (in_array($key, $this->ameexSyncPayloadKeys, true)) {
                    return false;
                }

                return is_scalar($value);
            })
            ->map(fn (mixed $value): string => match (true) {
                is_bool($value) => $value ? '1' : '0',
                default => (string) $value,
            })
            ->all();
    }

    /**
     * Nested sync payloads preserved outside the editable KeyValue form.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function preservedApiSettings(array $settings): array
    {
        return collect($settings)
            ->filter(function (mixed $value, mixed $key): bool {
                if (! is_string($key) || blank($key)) {
                    return false;
                }

                if (in_array($key, $this->ameexSyncPayloadKeys, true)) {
                    return true;
                }

                return ! is_scalar($value);
            })
            ->all();
    }
}
