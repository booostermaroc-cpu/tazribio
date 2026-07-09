<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DashboardMetrics;
use App\Support\CarrierStuckOrders;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParcelStatusOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full'];

    public function getHeading(): ?string
    {
        return __('codflow.dashboard.parcel_status');
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
            Stat::make(__('codflow.dashboard.delivered'), number_format($metrics['delivered']))
                ->description($metrics['delivered_trend']['text'])
                ->descriptionColor($metrics['delivered_trend']['color'])
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->url($this->ordersUrl(OrderStatus::Delivered)),
            Stat::make(__('codflow.dashboard.returned'), number_format($metrics['returned']))
                ->description(__('codflow.dashboard.distribution.returned'))
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->color('warning')
                ->url($this->ordersUrl(OrderStatus::Returned)),
            Stat::make(__('codflow.dashboard.cancelled'), number_format($metrics['cancelled']))
                ->description(__('codflow.dashboard.distribution.cancelled'))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->url($this->ordersUrl(OrderStatus::Cancelled)),
            Stat::make(__('codflow.dashboard.shipped'), number_format($metrics['shipped']))
                ->description(__('codflow.dashboard.shipped_hint'))
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->url($this->ordersUrl(OrderStatus::Shipped)),
            Stat::make(__('codflow.dashboard.stuck_at_carrier'), number_format($metrics['stuck_at_carrier']))
                ->description(__('codflow.dashboard.stuck_at_carrier_hint', [
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
