<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Support\DashboardMetrics;
use App\Models\AgentCommission;
use App\Models\Expense;
use App\Models\Order;
use App\Support\CarrierStuckOrders;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinancialMetrics
{
    public const CACHE_KEY = 'codflow.financial.metrics';

    public const CACHE_TTL = 180;

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        DashboardMetrics::clearCache();
    }

    /** @return array<string, mixed> */
    public static function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => self::compute());
    }

    /** @return array<string, mixed> */
    protected static function compute(): array
    {
        $monthStart = now()->startOfMonth();
        $todayStart = now()->startOfDay();

        $deliveredQuery = Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Delivered);
        $monthDelivered = (clone $deliveredQuery)->where('created_at', '>=', $monthStart);
        $todayDelivered = (clone $deliveredQuery)->where('created_at', '>=', $todayStart);

        $totalRevenue = (float) (clone $deliveredQuery)->sum('final_amount');
        $monthRevenue = (float) (clone $monthDelivered)->sum('final_amount');
        $todayRevenue = (float) (clone $todayDelivered)->sum('final_amount');

        $revenueByMethod = (clone $deliveredQuery)
            ->selectRaw('payment_method, SUM(final_amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($v) => (float) $v)
            ->all();

        $pendingPayments = (float) Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Delivered)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('final_amount');

        $purchaseCost = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', OrderStatus::Delivered->value)
            ->whereIn('orders.payment_method', PaymentMethod::collectedProfitValues())
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNull('orders.deleted_at')
            ->selectRaw('COALESCE(SUM(products.purchase_price * order_items.quantity), 0) as cost')
            ->value('cost');

        $monthExpenses = (float) Expense::query()->where('date', '>=', $monthStart)->sum('amount');
        $totalExpenses = (float) Expense::query()->sum('amount');

        $monthCommissions = (float) AgentCommission::query()->where('created_at', '>=', $monthStart)->sum('amount');
        $unpaidCommissions = (float) AgentCommission::query()->whereIn('status', ['pending', 'approved'])->sum('amount');
        $totalCommissions = (float) AgentCommission::query()
            ->whereNot('status', CommissionStatus::Cancelled)
            ->sum('amount');

        $carrierFees = (float) Order::query()
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Returned])
            ->sum('carrier_fee_amount');

        $monthCarrierFees = (float) Order::query()
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Returned])
            ->where('updated_at', '>=', $monthStart)
            ->sum('carrier_fee_amount');

        $carrierProjected = app(CarrierFeeService::class)->projectedShippedPayable();

        $profitService = app(OrderProfitService::class);
        $grossProfit = $profitService->totalProfit();
        $netProfit = $grossProfit - $totalExpenses - $totalCommissions;

        $totalOrders = Order::query()->count();
        $returned = Order::query()->where('status', OrderStatus::Returned)->count();
        $deliveredCount = Order::query()->where('status', OrderStatus::Delivered)->count();
        $cancelled = Order::query()->where('status', OrderStatus::Cancelled)->count();

        $returnRate = $totalOrders > 0 ? round(($returned / $totalOrders) * 100, 1) : 0;
        $deliverySuccessRate = ($deliveredCount + $returned) > 0
            ? round(($deliveredCount / ($deliveredCount + $returned)) * 100, 1)
            : 0;

        $returnLoss = (float) Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Returned)
            ->sum('final_amount');
        $cancelledImpact = (float) Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Cancelled)
            ->sum('final_amount');
        $stuckAtCarrierCount = CarrierStuckOrders::count();
        $stuckAtCarrierAmount = CarrierStuckOrders::totalAmount();

        $paymentChart = [];
        foreach (PaymentMethod::cases() as $method) {
            $paymentChart[$method->label()] = (float) ($revenueByMethod[$method->value] ?? 0);
        }

        return [
            'total_revenue' => $totalRevenue,
            'month_revenue' => $monthRevenue,
            'today_revenue' => $todayRevenue,
            'revenue_by_method' => $revenueByMethod,
            'payment_chart' => $paymentChart,
            'pending_payments' => $pendingPayments,
            'total_expenses' => $totalExpenses,
            'month_expenses' => $monthExpenses,
            'purchase_cost' => $purchaseCost,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'month_commissions' => $monthCommissions,
            'unpaid_commissions' => $unpaidCommissions,
            'carrier_fees' => $carrierFees,
            'month_carrier_fees' => $monthCarrierFees,
            'carrier_projected' => $carrierProjected,
            'carrier_breakdown' => app(CarrierFeeService::class)->breakdown(),
            'return_rate' => $returnRate,
            'delivery_success_rate' => $deliverySuccessRate,
            'return_loss' => $returnLoss,
            'cancelled_impact' => $cancelledImpact,
            'stuck_at_carrier_count' => $stuckAtCarrierCount,
            'stuck_at_carrier_amount' => $stuckAtCarrierAmount,
        ];
    }
}
