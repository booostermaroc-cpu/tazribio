<?php

namespace App\Filament\Widgets;

use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class OrderStatusChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = true;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
        'xl' => 3,
    ];

    protected string $color = 'primary';

    public function getHeading(): ?string
    {
        return __('codflow.dashboard.charts.status');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $distribution = DashboardMetrics::snapshot()['orders_distribution'];

        return [
            'datasets' => [
                [
                    'data' => array_values($distribution),
                    'backgroundColor' => ['#22c55e', '#f97316', '#ef4444', '#3b82f6'],
                    'borderWidth' => 3,
                    'borderColor' => '#ffffff',
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => array_keys($distribution),
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'cutout' => '68%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 12,
                        'color' => '#475569',
                        'font' => ['size' => 11, 'weight' => '500'],
                        'boxWidth' => 8,
                    ],
                ],
            ],
        ];
    }
}
