<?php

namespace App\Filament\Widgets;

use App\Services\FinancialMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = ['default' => 'full'];

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $m = FinancialMetrics::snapshot();

        return [
            Stat::make(__('codflow.finance.today_revenue'), number_format($m['today_revenue'], 2).' MAD')
                ->description(__('codflow.finance.revenue_today'))
                ->color('success'),
            Stat::make(__('codflow.finance.month_revenue'), number_format($m['month_revenue'], 2).' MAD')
                ->description(__('codflow.finance.revenue_month'))
                ->color('primary'),
            Stat::make(__('codflow.finance.month_expenses'), number_format($m['month_expenses'], 2).' MAD')
                ->description(__('codflow.finance.expenses_month'))
                ->color('danger'),
            Stat::make(__('codflow.finance.net_profit'), number_format($m['net_profit'], 2).' MAD')
                ->description(__('codflow.finance.net_profit_hint'))
                ->color('success'),
            Stat::make(__('codflow.finance.carrier_payable'), number_format($m['carrier_fees'], 2).' MAD')
                ->description(__('codflow.finance.carrier_payable_hint'))
                ->color('danger'),
            Stat::make(__('codflow.finance.carrier_payable_month'), number_format($m['month_carrier_fees'], 2).' MAD')
                ->description(__('codflow.finance.carrier_payable_month_hint'))
                ->color('warning'),
            Stat::make(__('codflow.finance.carrier_projected'), number_format($m['carrier_projected'], 2).' MAD')
                ->description(__('codflow.finance.carrier_projected_hint'))
                ->color('gray'),
            Stat::make(__('codflow.finance.pending_payments'), number_format($m['pending_payments'], 2).' MAD')
                ->description(__('codflow.finance.pending_hint'))
                ->color('warning'),
            Stat::make(__('codflow.finance.unpaid_commissions'), number_format($m['unpaid_commissions'], 2).' MAD')
                ->description(__('codflow.finance.commissions_hint'))
                ->color('info'),
            Stat::make(__('codflow.finance.return_rate'), $m['return_rate'].'%')
                ->description(__('codflow.finance.return_rate_hint'))
                ->color('danger'),
            Stat::make(__('codflow.finance.delivery_success'), $m['delivery_success_rate'].'%')
                ->description(__('codflow.finance.delivery_success_hint'))
                ->color('success'),
            Stat::make(__('codflow.finance.stuck_at_carrier'), number_format($m['stuck_at_carrier_amount'], 2).' MAD')
                ->description(__('codflow.finance.stuck_at_carrier_hint', [
                    'count' => $m['stuck_at_carrier_count'],
                    'days' => \App\Support\CarrierStuckOrders::thresholdDays(),
                ]))
                ->color('danger'),
        ];
    }
}
