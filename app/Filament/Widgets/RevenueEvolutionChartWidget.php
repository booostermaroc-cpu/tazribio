<?php

namespace App\Filament\Widgets;

use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class RevenueEvolutionChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 6,
    ];

    protected string $color = 'primary';

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return __('codflow.dashboard.charts.revenue_evolution');
    }

    protected function getFilters(): ?array
    {
        return [
            '14' => __('codflow.dashboard.charts.filter_14'),
            '30' => __('codflow.dashboard.charts.filter_30'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $metrics = DashboardMetrics::snapshot();
        $days = (int) ($this->filter ?? 30);
        $series = $days === 14 ? $metrics['revenue_per_day_14'] : $metrics['revenue_per_day_30'];

        return [
            'datasets' => [
                [
                    'label' => __('codflow.dashboard.charts.revenue_label'),
                    'data' => $series['data'],
                    'borderColor' => '#7c3aed',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.12)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2.5,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                    'pointBackgroundColor' => '#7c3aed',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'grid' => ['color' => 'rgba(148, 163, 184, 0.2)'],
                    'ticks' => ['color' => '#64748b'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#64748b', 'maxTicksLimit' => 8],
                ],
            ],
        ];
    }
}
