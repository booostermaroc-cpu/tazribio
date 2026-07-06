<?php

namespace App\Filament\Resources\ConfirmationTracking\Pages;

use App\Filament\Resources\ConfirmationTracking\ConfirmationTrackingResource;
use App\Filament\Widgets\ConfirmationAgentStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListConfirmationTracking extends ListRecords
{
    protected static string $resource = ConfirmationTrackingResource::class;

    public function getSubheading(): ?string
    {
        return __('codflow.confirmation_tracking.click_logged');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConfirmationAgentStatsWidget::class,
        ];
    }
}
