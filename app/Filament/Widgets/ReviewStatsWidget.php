<?php

namespace App\Filament\Widgets;

use App\Models\OrderReview;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReviewStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 0;

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
        $query = OrderReview::query()->whereNotNull('submitted_at');
        $count = (clone $query)->count();
        $avgProduct = round((float) (clone $query)->avg('product_rating'), 1);
        $avgService = round((float) (clone $query)->avg('service_rating'), 1);
        $monthCount = (clone $query)->where('submitted_at', '>=', now()->startOfMonth())->count();

        return [
            Stat::make(__('codflow.review.stats_total'), (string) $count)
                ->description(__('codflow.review.stats_total_hint'))
                ->color('primary'),
            Stat::make(__('codflow.review.stats_month'), (string) $monthCount)
                ->description(__('codflow.review.stats_month_hint'))
                ->color('info'),
            Stat::make(__('codflow.review.stats_avg_product'), $avgProduct.'/5')
                ->description(__('codflow.review.product_rating'))
                ->color('warning'),
            Stat::make(__('codflow.review.stats_avg_service'), $avgService.'/5')
                ->description(__('codflow.review.service_rating'))
                ->color('success'),
        ];
    }
}
