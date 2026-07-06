<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\DashboardMetrics;
use App\Models\Product;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LowStockAlertsWidget extends Widget
{
    protected static ?int $sort = 6;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 5,
    ];

    protected string $view = 'filament.widgets.low-stock-alerts';

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return DashboardMetrics::snapshot()['low_stock_products'];
    }

    public function getViewAllUrl(): string
    {
        return ProductResource::getUrl('index');
    }
}
