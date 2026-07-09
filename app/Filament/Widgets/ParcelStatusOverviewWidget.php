<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DashboardLabels;
use App\Filament\Support\DashboardMetrics;
use App\Support\CarrierStuckOrders;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParcelStatusOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full'];

    protected function getHeading(): ?string
    {
        return DashboardLabels::get('parcel_status');
    }

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'sm' => 3,
            'lg' => 5,
        ];
    }

    protected function getStats(): array
    {
        $metrics = DashboardMetrics::snapshot();

        return [
            Stat::make(DashboardLabels::get('delivered'), number_format($metrics['delivered']))
                ->description($metrics['delivered_trend']['text'])
                ->descriptionColor($metrics['delivered_trend']['color'])
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->url($this->ordersUrl(OrderStatus::Delivered)),
            Stat::make(DashboardLabels::get('returned'), number_format($metrics['returned']))
                ->description(DashboardLabels::get('distribution.returned'))
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->color('warning')
                ->url($this->ordersUrl(OrderStatus::Returned)),
            Stat::make(DashboardLabels::get('cancelled'), number_format($metrics['cancelled']))
                ->description(DashboardLabels::get('distribution.cancelled'))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->url($this->ordersUrl(OrderStatus::Cancelled)),
            Stat::make(DashboardLabels::get('shipped'), number_format($metrics['shipped']))
                ->description(DashboardLabels::get('shipped_hint'))
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->url($this->ordersUrl(OrderStatus::Shipped)),
            Stat::make(DashboardLabels::get('stuck_at_carrier'), number_format($metrics['stuck_at_carrier']))
                ->description(DashboardLabels::get('stuck_at_carrier_hint', [
                    'count' => number_format($metrics['stuck_at_carrier']),
                    'days' => CarrierStuckOrders::thresholdDays(),
                ]))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedClock)
                ->color('danger')
                ->url($this->stuckOrdersUrl()),
        ];
    }

    protected function ordersUrl(OrderStatus $status): string
    {
        return OrderResource::getUrl('index', [
            'tableFilters' => [
                'status' => [
                    'value' => $status->value,
                ],
            ],
        ]);
    }

    protected function stuckOrdersUrl(): string
    {
        return OrderResource::getUrl('index', [
            'tableFilters' => [
                'stuck_at_carrier' => [
                    'isActive' => true,
                ],
            ],
        ]);
    }
}
