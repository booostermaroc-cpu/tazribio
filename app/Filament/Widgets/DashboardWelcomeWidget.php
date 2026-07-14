<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardWelcomeWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-welcome';
}
