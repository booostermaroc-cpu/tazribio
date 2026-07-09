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
            Stat::make(__('codflow.finance.month_revenue'), number_format($m['month_revenue'], 2).' MAD')
                ->description(__('codflow.finance.revenue_month'))
                ->color('primary'),
            Stat::make(__('codflow.finance.month_expenses'), number_format($m['month_expenses'], 2).' MAD')
                ->description(__('codflow.finance.expenses_month'))
                ->color('danger'),
            Stat::make(__('codflow.finance.net_profit'), number_format($m['net_profit'], 2).' MAD')
                ->description(__('codflow.finance.net_profit_hint'))
                ->color('success'),
            Stat::make(__('codflow.finance.unpaid_commissions'), number_format($m['unpaid_commissions'], 2).' MAD')
                ->description(__('codflow.finance.commissions_hint'))
                ->color('info'),
            Stat::make(__('codflow.finance.return_rate'), $m['return_rate'].'%')
                ->description(__('codflow.finance.return_rate_hint'))
                ->color('danger'),
            Stat::make(__('codflow.finance.delivery_success'), $m['delivery_success_rate'].'%')
                ->description(__('codflow.finance.delivery_success_hint'))
                ->color('success'),
        ];
    }
}
