<?php

namespace App\Filament\Resources\DeliveryCompanies\Concerns;

trait InteractsWithAmeexBusinessIdForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $settings = is_array($data['api_settings'] ?? null) ? $data['api_settings'] : [];
        $data['ameex_business_id'] = (string) ($settings['business_id'] ?? '');
        $data['ameex_send_without_stock'] = in_array(strtoupper((string) ($settings['send_without_stock_check'] ?? '0')), ['1', 'TRUE', 'YES', 'OUI'], true);
        $data['api_settings'] = $this->sanitizeApiSettings($settings);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $settings = is_array($data['api_settings'] ?? null) ? $data['api_settings'] : [];

        if (filled($data['ameex_business_id'] ?? null)) {
            $settings['business_id'] = trim((string) $data['ameex_business_id']);
        }

        $settings['send_without_stock_check'] = ($data['ameex_send_without_stock'] ?? false) ? '1' : '0';

        unset($data['ameex_business_id'], $data['ameex_send_without_stock']);

        $data['api_settings'] = $this->sanitizeApiSettings($settings);

        if (filled($data['api_base_url'] ?? null)) {
            $data['api_base_url'] = str_replace('http://api.ameex.app', 'https://api.ameex.app', (string) $data['api_base_url']);
        }

        return $data;
    }

    /** @param  array<string, mixed>  $settings */
    protected function sanitizeApiSettings(array $settings): array
    {
        return collect($settings)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key) && filled($key))
            ->all();
    }
}
