<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public static function get(): Setting
    {
        return Setting::query()->firstOrCreate([], [
            'company_name' => 'CODFlow',
            'default_delivery_fee' => 30,
            'carrier_fee_rules' => CarrierFeeService::defaultRules(),
            'order_prefix' => 'ORD',
            'invoice_prefix' => 'INV',
            'carrier_stuck_days' => 60,
        ]);
    }

    public static function logoUrl(): ?string
    {
        $logo = static::get()->logo;

        if (blank($logo)) {
            return null;
        }

        if (Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }

        if (Storage::disk('local')->exists($logo)) {
            return Storage::disk('local')->url($logo);
        }

        return asset('storage/'.$logo);
    }

    public static function companyName(): string
    {
        return static::get()->company_name ?: __('codflow.brand');
    }
}
