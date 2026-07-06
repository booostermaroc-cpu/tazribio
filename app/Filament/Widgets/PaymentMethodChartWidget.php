<?php

namespace App\Filament\Widgets;

use App\Services\FinancialMetrics;
use Filament\Widgets\ChartWidget;

class PaymentMethodChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 6,
    ];

    public function getHeading(): ?string
    {
        return __('codflow.finance.payment_methods_chart');
    }

    protected function getData(): array
    {
        $chart = FinancialMetrics::snapshot()['payment_chart'] ?? [];

        return [
            'datasets' => [
                [
                    'data' => array_values($chart),
                    'backgroundColor' => ['#7c3aed', '#06b6d4', '#f59e0b', '#10b981', '#94a3b8'],
                ],
            ],
            'labels' => array_keys($chart),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): ?array
    {
        return [
            'cutout' => '60%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 10,
                        'font' => ['size' => 11],
                    ],
                ],
            ],
        ];
    }
}
