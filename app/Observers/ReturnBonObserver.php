<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Filament\Support\DashboardMetrics;
use App\Models\ReturnBon;
use App\Services\CarrierFeeService;
use App\Services\FinancialMetrics;

class ReturnBonObserver
{
    public function saved(ReturnBon $returnBon): void
    {
        $order = $returnBon->order;

        if (! $order || $order->status !== OrderStatus::Returned) {
            return;
        }

        app(CarrierFeeService::class)->syncOrderFee($order);

        DashboardMetrics::clearCache();
        FinancialMetrics::clearCache();
    }
}
