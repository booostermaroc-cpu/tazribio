<?php

namespace App\Filament\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\CarrierFeeService;
use App\Services\OrderProfitService;
use App\Services\SettingService;
use App\Support\CarrierStuckOrders;
use App\Support\OrderWorkflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    public const CACHE_KEY = 'codflow.dashboard.metrics';

    public const CACHE_TTL = 180;

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('codflow.nav.orders_badge');
        Cache::forget('codflow.nav.stock_badge');
    }

    /** @return array<string, mixed> */
    public static function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => self::computeSnapshot());
    }

    /** @return array<string, mixed> */
    protected static function computeSnapshot(): array
    {
        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $delivered = (int) ($statusCounts[OrderStatus::Delivered->value] ?? 0);
        $returned = (int) ($statusCounts[OrderStatus::Returned->value] ?? 0);
        $cancelled = (int) ($statusCounts[OrderStatus::Cancelled->value] ?? 0);
        $totalOrders = (int) $statusCounts->sum();

        $inProgress = $totalOrders - $delivered - $returned - $cancelled;
        $stuckAtCarrier = CarrierStuckOrders::count();
        $stuckAtCarrierAmount = CarrierStuckOrders::totalAmount();
        $inProgressActive = max(0, $inProgress - $stuckAtCarrier);

        $revenue = (float) Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Delivered)
            ->sum('final_amount');

        $estimatedProfit = self::estimatedGrossProfit();
        $carrierPayable = app(CarrierFeeService::class)->totalPayable();
        $carrierMonthPayable = app(CarrierFeeService::class)->monthPayable();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $prevMonthStart = now()->subMonth()->startOfMonth();
        $prevMonthEnd = now()->subMonth()->endOfMonth();

        $currentMonthOrders = Order::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $prevMonthOrders = Order::query()->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();

        $currentMonthDelivered = Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
        $prevMonthDelivered = Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $currentMonthRevenue = (float) Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('final_amount');
        $prevMonthRevenue = (float) Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->sum('final_amount');

        return [
            'total_orders' => $totalOrders,
            'delivered' => $delivered,
            'returned' => $returned,
            'cancelled' => $cancelled,
            'stuck_at_carrier' => $stuckAtCarrier,
            'stuck_at_carrier_amount' => $stuckAtCarrierAmount,
            'revenue' => $revenue,
            'estimated_profit' => $estimatedProfit,
            'carrier_payable' => $carrierPayable,
            'carrier_payable_month' => $carrierMonthPayable,
            'orders_trend' => self::formatTrend($currentMonthOrders, $prevMonthOrders),
            'delivered_trend' => self::formatTrend($currentMonthDelivered, $prevMonthDelivered),
            'revenue_trend' => self::formatTrend($currentMonthRevenue, $prevMonthRevenue),
            'orders_distribution' => [
                __('codflow.dashboard.distribution.delivered') => $delivered,
                __('codflow.dashboard.distribution.returned') => $returned,
                __('codflow.dashboard.distribution.cancelled') => $cancelled,
                __('codflow.dashboard.distribution.stuck_at_carrier') => $stuckAtCarrier,
                __('codflow.dashboard.distribution.in_progress') => $inProgressActive,
            ],
            'revenue_per_day_14' => self::revenuePerDay(14),
            'revenue_per_day_30' => self::revenuePerDay(30),
            'top_products' => self::topProducts(5),
            'low_stock_products' => self::lowStockProducts(5),
        ];
    }

    /** @return array{text: string, color: string} */
    public static function formatTrend(float|int $current, float|int $previous): array
    {
        if ($previous <= 0) {
            return [
                'text' => '— '.__('codflow.dashboard.vs_last_month'),
                'color' => 'gray',
            ];
        }

        $percent = (($current - $previous) / $previous) * 100;
        $arrow = $percent >= 0 ? '↑' : '↓';

        return [
            'text' => $arrow.' '.number_format(abs($percent), 1).'% '.__('codflow.dashboard.vs_last_month'),
            'color' => $percent >= 0 ? 'success' : 'danger',
        ];
    }

    /** @return array{labels: list<string>, data: list<float>} */
    public static function revenuePerDay(int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->excludingCod()
            ->where('created_at', '>=', $from)
            ->where('status', OrderStatus::Delivered)
            ->selectRaw('DATE(created_at) as day, SUM(final_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return self::fillDailySeries($days, $rows);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, float|int|string>  $rows
     * @return array{labels: list<string>, data: list<float>}
     */
    protected static function fillDailySeries(int $days, Collection $rows): array
    {
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $data[] = (float) ($rows[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    public static function estimatedGrossProfit(): float
    {
        return app(OrderProfitService::class)->totalProfit();
    }

    /** @return Collection<int, Product> */
    public static function topProducts(int $limit = 5): Collection
    {
        return Product::query()
            ->select('products.*')
            ->selectSub(
                DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.status', OrderStatus::Delivered->value)
                    ->whereNull('orders.deleted_at')
                    ->whereColumn('order_items.product_id', 'products.id')
                    ->selectRaw('COALESCE(SUM(order_items.quantity), 0)'),
                'total_sold'
            )
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Product> */
    public static function lowStockProducts(int $limit = 5): Collection
    {
        return Product::query()
            ->whereColumn('current_stock', '<=', 'stock_alert')
            ->orderBy('current_stock')
            ->limit($limit)
            ->get();
    }

    public static function newOrdersBadgeCount(): int
    {
        return (int) Cache::remember('codflow.nav.orders_badge', self::CACHE_TTL, fn () => Order::query()
            ->whereIn('status', array_map(
                fn (OrderStatus $status): string => $status->value,
                OrderWorkflow::confirmationPhaseStatuses(),
            ))
            ->count());
    }

    public static function lowStockBadgeCount(): int
    {
        return (int) Cache::remember('codflow.nav.stock_badge', self::CACHE_TTL, fn () => Product::query()
            ->whereColumn('current_stock', '<=', 'stock_alert')
            ->count());
    }
}
