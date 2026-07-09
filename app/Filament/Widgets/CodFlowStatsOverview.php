<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DashboardLabels;
use App\Filament\Support\DashboardMetrics;
use App\Services\SettingService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CodFlowStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 'full'];

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 4,
        ];
    }

    protected function getStats(): array
    {
        $metrics = DashboardMetrics::snapshot();
        $settings = SettingService::get();
        $profitHint = $settings->use_manual_profit_total
            ? DashboardLabels::get('profit_manual_hint')
            : DashboardLabels::get('profit_hint');

        return [
            Stat::make(DashboardLabels::get('total_orders'), number_format($metrics['total_orders']))
                ->description($metrics['orders_trend']['text'])
                ->descriptionColor($metrics['orders_trend']['color'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('primary'),
            Stat::make(DashboardLabels::get('delivered'), number_format($metrics['delivered']))
                ->description($metrics['delivered_trend']['text'])
                ->descriptionColor($metrics['delivered_trend']['color'])
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->icon(Heroicon::OutlinedTruck)
                ->color('success'),
            Stat::make(DashboardLabels::get('returned'), number_format($metrics['returned']))
                ->description(DashboardLabels::get('distribution.returned'))
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->color('warning'),
            Stat::make(DashboardLabels::get('cancelled'), number_format($metrics['cancelled']))
                ->description(DashboardLabels::get('distribution.cancelled'))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger'),
            Stat::make(DashboardLabels::get('stuck_at_carrier'), number_format($metrics['stuck_at_carrier_amount'], 0, ',', ' ').' MAD')
                ->description(DashboardLabels::get('stuck_at_carrier_hint', [
                    'count' => number_format($metrics['stuck_at_carrier']),
                    'days' => \App\Support\CarrierStuckOrders::thresholdDays(),
                ]))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedClock)
                ->color('danger')
                ->url(OrderResource::getUrl('index')),
            Stat::make(DashboardLabels::get('revenue'), number_format($metrics['revenue'], 0, ',', ' ').' MAD')
                ->description($metrics['revenue_trend']['text'])
                ->descriptionColor($metrics['revenue_trend']['color'])
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('info')
                ->extraAttributes(['title' => DashboardLabels::get('revenue_hint')]),
            Stat::make(DashboardLabels::get('profit'), number_format($metrics['estimated_profit'], 0, ',', ' ').' MAD')
                ->description($profitHint)
                ->descriptionColor($metrics['revenue_trend']['color'])
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->color('primary'),
            Stat::make(DashboardLabels::get('carrier_payable'), number_format($metrics['carrier_payable'], 0, ',', ' ').' MAD')
                ->description(DashboardLabels::get('carrier_payable_month', ['amount' => number_format($metrics['carrier_payable_month'], 0, ',', ' ')]))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedTruck)
                ->color('danger'),
        ];
    }
}
