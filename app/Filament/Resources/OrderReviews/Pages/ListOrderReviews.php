<?php

namespace App\Filament\Resources\OrderReviews\Pages;

use App\Filament\Resources\OrderReviews\OrderReviewResource;
use App\Filament\Widgets\ReviewStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListOrderReviews extends ListRecords
{
    protected static string $resource = OrderReviewResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ReviewStatsWidget::class,
        ];
    }
}
