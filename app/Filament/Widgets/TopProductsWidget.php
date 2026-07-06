<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\DashboardMetrics;
use App\Models\Product;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class TopProductsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
        'xl' => 3,
    ];

    protected string $view = 'filament.widgets.top-products';

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return DashboardMetrics::snapshot()['top_products'];
    }

    public function getViewAllUrl(): string
    {
        return ProductResource::getUrl('index');
    }
}
