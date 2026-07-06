<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Support\Nav;
use App\Services\CarrierFeeService;
use App\Services\FinancialMetrics;
use App\Services\SettingService;
use Filament\Resources\Pages\EditRecord;

class ManageSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected static ?string $title = null;

    public function getTitle(): string
    {
        return Nav::label('settings');
    }

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return Nav::label('settings');
    }

    public function mount(int|string|null $record = null): void
    {
        parent::mount(SettingService::get()->getKey());

        if (blank($this->record->carrier_fee_rules)) {
            $this->record->update([
                'carrier_fee_rules' => CarrierFeeService::defaultRules(),
            ]);
            $this->record->refresh();
            $this->fillForm();
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        $rules = $this->record->carrier_fee_rules;

        if (is_array($rules)) {
            $this->record->update([
                'carrier_fee_rules' => CarrierFeeService::normalizeRules($rules),
            ]);
        }

        app(CarrierFeeService::class)->syncAllOrders();
        FinancialMetrics::clearCache();
    }
}
