<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public static function get(): Setting
    {
        return Setting::query()->firstOrCreate([], [
            'company_name' => 'Tazri Bio',
            'default_delivery_fee' => 15,
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

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        if ($publicDisk->exists($logo)) {
            return $publicDisk->url($logo);
        }

        /** @var FilesystemAdapter $localDisk */
        $localDisk = Storage::disk('local');

        if ($localDisk->exists($logo)) {
            return $localDisk->url($logo);
        }

        return asset('storage/'.$logo);
    }

    public static function companyName(): string
    {
        return static::get()->company_name ?: __('codflow.brand');
    }
}
