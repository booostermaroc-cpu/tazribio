<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderProfitService
{
    public function eligibleForAutoProfit(Order $order): bool
    {
        if ($order->payment_method === PaymentMethod::Cod) {
            return false;
        }

        if ($order->status !== OrderStatus::Delivered) {
            return false;
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            return false;
        }

        return $order->payment_method?->countsForCollectedProfit() ?? false;
    }

    public function calculateAuto(Order $order): float
    {
        if (! $this->eligibleForAutoProfit($order)) {
            return 0.0;
        }

        $margin = (float) DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.order_id', $order->id)
            ->selectRaw('COALESCE(SUM(order_items.total_price - (products.purchase_price * order_items.quantity)), 0) as profit')
            ->value('profit');

        if (SettingService::get()->profit_include_delivery_fee) {
            $margin -= (float) $order->delivery_fee;
        }

        $margin -= (float) $order->carrier_fee_amount;

        return round(max(0, $margin), 2);
    }

    public function resolve(Order $order): float
    {
        if ($order->payment_method === PaymentMethod::Cod) {
            return 0.0;
        }

        if ($order->profit_is_manual && $order->profit_amount !== null) {
            return (float) $order->profit_amount;
        }

        return $this->calculateAuto($order);
    }

    public function syncAutoProfit(Order $order): void
    {
        if ($order->payment_method === PaymentMethod::Cod) {
            $order->forceFill([
                'profit_amount' => 0,
                'profit_is_manual' => false,
            ])->saveQuietly();

            return;
        }

        if ($order->profit_is_manual) {
            return;
        }

        $order->forceFill([
            'profit_amount' => $this->calculateAuto($order),
        ])->saveQuietly();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Order> */
    public function profitEligibleQuery()
    {
        return Order::query()
            ->excludingCod()
            ->where('status', OrderStatus::Delivered)
            ->where(function ($query): void {
                $query->where('profit_is_manual', true)
                    ->orWhere(function ($inner): void {
                        $inner->where('profit_is_manual', false)
                            ->where('payment_status', PaymentStatus::Paid)
                            ->whereIn('payment_method', PaymentMethod::collectedProfitValues());
                    });
            });
    }

    public function totalProfit(): float
    {
        $settings = SettingService::get();

        if ($settings->use_manual_profit_total) {
            return round((float) ($settings->manual_profit_total ?? 0), 2);
        }

        return (float) $this->profitEligibleQuery()
            ->get()
            ->sum(fn (Order $order): float => $this->resolve($order));
    }

    public function monthProfit(): float
    {
        if (SettingService::get()->use_manual_profit_total) {
            return 0.0;
        }

        $monthStart = now()->startOfMonth();

        return (float) $this->profitEligibleQuery()
            ->where('updated_at', '>=', $monthStart)
            ->get()
            ->sum(fn (Order $order): float => $this->resolve($order));
    }
}
