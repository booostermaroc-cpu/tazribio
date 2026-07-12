<?php

namespace App\Filament\Pages;

use App\Filament\Support\DashboardLabels;
use App\Filament\Support\PanelHome;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && RolePermission::canAccessResource($user, 'dashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mountCanAuthorizeAccess(): void
    {
        if (! static::canAccess()) {
            $this->redirect(PanelHome::url(), navigate: true);
        }
    }

    public static function getNavigationLabel(): string
    {
        return DashboardLabels::get('title');
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 12,
        ];
    }

    /** @return array<class-string> */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\FinancialOverviewWidget::class,
            \App\Filament\Widgets\ParcelStatusOverviewWidget::class,
            \App\Filament\Widgets\OrderStatusChartWidget::class,
            \App\Filament\Widgets\RevenueEvolutionChartWidget::class,
            \App\Filament\Widgets\PaymentMethodChartWidget::class,
            \App\Filament\Widgets\TopProductsWidget::class,
            \App\Filament\Widgets\LatestOrdersWidget::class,
            \App\Filament\Widgets\LowStockAlertsWidget::class,
        ];
    }
}
